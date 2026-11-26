<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCorrectionController extends Controller
{
    public const OPEN_STATUSES = ['pending', 'department_reviewed'];

    public static function available(User $user): bool
    {
        return $user->is_active && $user->role?->name === 'HRMO Head';
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(self::available($user), 403);
        $employeeFilter = $request->validate(['employee' => ['sometimes', 'required', 'integer', 'min:1']])['employee'] ?? null;
        $employee = $employeeFilter ? Employee::findOrFail($employeeFilter) : null;
        $query = AttendanceCorrection::query()->with(['attendanceRecord', 'employee' => fn ($query) => $query->addSelect(['avatar' => Employee::accountAvatarQuery()])]);

        if ($employeeFilter) {
            $query->where('employee_id', $employeeFilter);
        }

        return Inertia::render('hris/attendance-corrections', [
            'records' => $query->latest('id')->simplePaginate(20)->withQueryString()->through(fn (AttendanceCorrection $row) => [
                'id' => $row->id,
                'date' => $row->attendanceRecord->attendance_date ?? ($row->proposed_time_in ? substr($row->proposed_time_in, 0, 10) : null),
                'missingDay' => $row->correction_type === 'missing_day',
                'employee' => $row->employee?->employee_number,
                'avatar' => $row->employee?->avatar,
                'timeIn' => $row->proposed_time_in,
                'timeOut' => $row->proposed_time_out,
                'reason' => $row->reason,
                'status' => $row->status,
                'canReview' => $row->employee_id !== $user->employee_id && $row->employee?->user_id !== $user->id
                    && in_array($row->status, self::OPEN_STATUSES, true),
            ]),
            'logs' => $employee && $employee->id !== $user->employee_id && $employee->user_id !== $user->id
                ? $employee->attendanceRecords()->orderByDesc('attendance_date')->latest('id')->limit(60)->get(['id', 'attendance_date', 'time_in', 'time_out'])
                : null,
            'advanceLabel' => 'Approve correction',
            'employeeId' => $employee?->id,
            'employeeNumber' => $employee?->employee_number,
            'canManageAttendance' => $user->can('manage-hris-resource', 'attendance'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(self::available($user), 403);
        $data = $request->validate([
            'employee' => ['required', 'integer', 'min:1'],
            'attendance_record_id' => ['nullable', 'integer'],
            'time_in' => ['required', 'date_format:Y-m-d\TH:i'],
            'time_out' => ['required', 'date_format:Y-m-d\TH:i', 'after:time_in'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $owner = Employee::query()->lockForUpdate()->findOrFail($data['employee']);
            abort_if($owner->user_id === $user->id || $owner->id === $user->employee_id, 403);

            $log = isset($data['attendance_record_id'])
                ? $owner->attendanceRecords()->lockForUpdate()->findOrFail((int) $data['attendance_record_id'])
                : null;
            $start = Carbon::parse($data['time_in']);
            $end = Carbon::parse($data['time_out']);
            $date = $start->toDateString();
            if (($log && $date !== $log->attendance_date) || $start->diffInHours($end) > 24 || $end->isFuture()) {
                throw ValidationException::withMessages(['time_in' => 'Use the correct attendance date and a completed shift of at most 24 hours.']);
            }

            if (! $log && $owner->attendanceRecords()->where('attendance_date', $date)->exists()) {
                throw ValidationException::withMessages(['attendance_record_id' => 'An attendance record already exists for this day. Select it to request a correction.']);
            }

            $pending = $owner->attendanceCorrections()->whereIn('status', self::OPEN_STATUSES);
            if ($log) {
                $pending->where('attendance_record_id', $log->id);
            } else {
                $pending->whereNull('attendance_record_id')->whereDate('proposed_time_in', $date);
            }

            if ($pending->exists()) {
                throw ValidationException::withMessages(['attendance_record_id' => 'There is already a correction under review for this record or day.']);
            }

            $owner->attendanceCorrections()->create([
                'attendance_record_id' => $log?->id,
                'correction_type' => $log ? 'time_log' : 'missing_day',
                'proposed_time_in' => $start->toDateTimeString(),
                'proposed_time_out' => $end->toDateTimeString(),
                'reason' => $data['reason'],
            ]);
        });

        return to_route('hris.attendance-corrections.index', ['employee' => $data['employee']]);
    }

    public function review(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $user = $request->user();
        abort_unless(self::available($user), 403);
        $data = $request->validate(['decision' => ['required', 'in:advance,reject']]);

        DB::transaction(function () use ($user, $data, $correction): void {
            $owner = $correction->employee()->lockForUpdate()->firstOrFail();
            $entry = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);

            abort_if($owner->user_id === $user->id || $owner->id === $user->employee_id, 403);

            if (! in_array($entry->status, self::OPEN_STATUSES, true) || ! $entry->proposed_time_in || ! $entry->proposed_time_out) {
                throw ValidationException::withMessages(['decision' => 'This request is no longer available for this decision.']);
            }

            $next = $data['decision'] === 'reject' ? 'rejected' : 'approved';
            if ($next === 'approved') {
                $times = [
                    'time_in' => $entry->proposed_time_in,
                    'time_out' => $entry->proposed_time_out,
                    'total_hours' => round(Carbon::parse($entry->proposed_time_in)->diffInHours(Carbon::parse($entry->proposed_time_out)), 2),
                ];
                if ($entry->attendance_record_id) {
                    $log = $owner->attendanceRecords()->lockForUpdate()->findOrFail($entry->attendance_record_id);
                    $log->update($times);
                } else {
                    $date = substr($entry->proposed_time_in, 0, 10);
                    if ($owner->attendanceRecords()->where('attendance_date', $date)->exists()) {
                        throw ValidationException::withMessages(['decision' => 'A record now exists for this day. Reject this request and have the employee request a correction to that record.']);
                    }
                    $log = $owner->attendanceRecords()->create($times + [
                        'attendance_date' => $date,
                        'source' => 'approved_correction',
                        'status' => 'present',
                    ]);
                    $entry->attendance_record_id = $log->id;
                }
                $entry->approvedBy()->associate($user);
                $entry->approved_at = now();
            }

            $entry->status = $next;
            $entry->save();
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'attendance_correction.'.$next,
                'model_type' => AttendanceCorrection::class,
                'model_id' => $entry->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance correction decision saved.']);

        return back();
    }
}
