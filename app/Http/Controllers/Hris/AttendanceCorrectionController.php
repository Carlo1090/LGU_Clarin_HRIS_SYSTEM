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
    private const ROLES = ['Employee', 'Department Head', 'HR Officer', 'HRMO Head'];

    public static function available(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, self::ROLES, true);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(self::available($user), 403);
        $role = $user->role?->name;
        $employee = $user->employee;
        $linked = $employee && $employee->user_id === $user->id;
        $query = AttendanceCorrection::query()->with(['attendanceRecord', 'employee' => fn ($query) => $query->addSelect(['avatar' => Employee::accountAvatarQuery()])]);

        if ($role === 'Employee') {
            $query->where('employee_id', $linked ? $employee->id : 0);
        } elseif ($role === 'Department Head') {
            $query->whereHas('employee', fn ($owner) => $owner->where('department_id', $linked ? ($employee->department_id ?? 0) : 0));
        }

        return Inertia::render('hris/attendance-corrections', [
            'records' => $query->latest('id')->simplePaginate(20)->through(fn (AttendanceCorrection $row) => [
                'id' => $row->id,
                'date' => $row->attendanceRecord->attendance_date ?? ($row->proposed_time_in ? substr($row->proposed_time_in, 0, 10) : null),
                'missingDay' => $row->correction_type === 'missing_day',
                'employee' => $row->employee?->employee_number,
                'avatar' => $row->employee?->avatar,
                'timeIn' => $row->proposed_time_in,
                'timeOut' => $row->proposed_time_out,
                'reason' => $row->reason,
                'status' => $row->status,
                'canReview' => $row->employee_id !== $user->employee_id && match ($role) {
                    'Department Head' => $row->status === 'pending',
                    'HR Officer', 'HRMO Head' => $row->status === 'department_reviewed',
                    default => false,
                },
            ]),
            'logs' => $role === 'Employee' && $linked
                ? $employee->attendanceRecords()->orderByDesc('attendance_date')->latest('id')->limit(60)->get(['id', 'attendance_date', 'time_in', 'time_out'])
                : null,
            'advanceLabel' => $role === 'Department Head' ? 'Forward to HRMO' : 'Validate and apply',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        abort_unless(self::available($user) && $user->role?->name === 'Employee' && $employee && $employee->user_id === $user->id, 403);
        $data = $request->validate([
            'attendance_record_id' => ['nullable', 'integer'],
            'time_in' => ['required', 'date_format:Y-m-d\TH:i'],
            'time_out' => ['required', 'date_format:Y-m-d\TH:i', 'after:time_in'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($employee, $data): void {
            $owner = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            abort_unless($owner->user_id === $employee->user_id, 403);

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

            $pending = $owner->attendanceCorrections()->whereIn('status', ['pending', 'department_reviewed']);
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

        return to_route('hris.attendance-corrections.index');
    }

    public function review(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $user = $request->user();
        $role = $user->role?->name;
        abort_unless(self::available($user) && $role !== 'Employee', 403);
        $data = $request->validate(['decision' => ['required', 'in:advance,reject']]);

        DB::transaction(function () use ($user, $role, $data, $correction): void {
            $owner = $correction->employee()->lockForUpdate()->firstOrFail();
            $entry = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);

            abort_if($owner->user_id === $user->id || $owner->id === $user->employee_id, 403);

            if ($role === 'Department Head') {
                $head = $user->employee;
                abort_unless($head && $head->user_id === $user->id && $head->department_id && $head->department_id === $owner->department_id, 403);
            }

            $expected = $role === 'Department Head' ? 'pending' : 'department_reviewed';
            if ($entry->status !== $expected || ! $entry->proposed_time_in || ! $entry->proposed_time_out) {
                throw ValidationException::withMessages(['decision' => 'This request is no longer available for this decision.']);
            }

            $next = $data['decision'] === 'reject' ? 'rejected' : ($role === 'Department Head' ? 'department_reviewed' : 'approved');
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

        return back();
    }
}
