<?php

namespace App\Http\Controllers\Hris;

use App\Attendance\Services\AttendanceRecomputer;
use App\Attendance\Services\PunchClassifier;
use App\Attendance\Services\ScheduleResolver;
use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCorrectionController extends Controller
{
    public const OPEN_STATUSES = ['pending', 'department_reviewed'];

    public static function available(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Employee', 'HR Officer', 'HRMO Head'], true);
    }

    public function index(Request $request, AttendanceRecomputer $attendance): Response
    {
        $user = $request->user();
        abort_unless(self::available($user), 403);
        $filters = $request->validate([
            'employee' => ['sometimes', 'required', 'integer', 'min:1'],
            'request' => ['sometimes', 'required', 'integer', 'min:1'],
            'month' => ['sometimes', 'required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
        ]);

        $self = $user->role?->name === 'Employee';
        $employee = $self ? $this->linkedEmployee($user)
            : (isset($filters['employee']) ? Employee::findOrFail($filters['employee']) : null);
        if ($self && isset($filters['employee'])) {
            abort_unless($employee->id === (int) $filters['employee'], 403);
        }

        $month = $filters['month'] ?? now('Asia/Manila')->format('Y-m');
        $from = $month.'-01';
        $to = Carbon::parse($from)->endOfMonth()->toDateString();
        $query = AttendanceCorrection::query()->with(['attendanceRecord', 'employee', 'filer'])
            ->when($employee, fn ($query) => $query->where('employee_id', $employee->id));
        $selected = isset($filters['request']) ? (clone $query)->findOrFail($filters['request']) : null;
        if ($employee) {
            $query->forWorkDates($from, $to);
        }
        $flagged = $employee ? $attendance->reviewSummary($employee, $from, $to)['flagged'] : [];
        $punches = [];
        if ($self) {
            foreach ($attendance->recordedWorkDates($employee->id, $from, $to) as $date) {
                $punches[$date] = array_column($attendance->employeeDay($employee->id, $date)['punches'], 'punched_at');
            }
        }

        $details = null;
        if ($selected) {
            $details = $this->row($selected, $user);
            $details['punches'] = $details['date']
                ? array_column($attendance->employeeDay($selected->employee_id, $details['date'])['punches'], 'punched_at')
                : [];
        }

        return Inertia::render('hris/attendance-corrections', [
            'records' => $query->orderByRaw("CASE WHEN status IN ('pending', 'department_reviewed') THEN 0 ELSE 1 END")
                ->latest('id')->simplePaginate(20)->withQueryString()->through(fn ($row) => $this->row($row, $user)),
            'selected' => $details,
            'employeeId' => $employee?->id,
            'employeeNumber' => $employee?->employee_number,
            'employeeView' => $self,
            'month' => $month,
            'flagged' => (object) $flagged,
            'punches' => (object) $punches,
            'canFile' => $employee !== null && ($self || $user->role?->name === 'HR Officer'),
            'canManageAttendance' => $user->can('manage-hris-resource', 'attendance'),
        ]);
    }

    public function store(Request $request, AttendanceRecomputer $attendance): RedirectResponse
    {
        $user = $request->user();
        abort_unless(self::available($user) && $user->role?->name !== 'HRMO Head', 403);
        $self = $user->role?->name === 'Employee';
        $data = $request->validate([
            'employee' => [$self ? 'nullable' : 'required', 'integer', 'min:1'],
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'slot' => ['required', Rule::in(AttendanceCorrection::SLOTS)],
            'proposed_at' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:2000'],
            'evidence' => ['nullable', 'string', 'max:2000'],
        ]);
        $employeeId = $self ? $this->linkedEmployee($user)->id : (int) $data['employee'];
        abort_if($self && isset($data['employee']) && (int) $data['employee'] !== $employeeId, 403);
        $date = $data['work_date'];
        $proposed = Carbon::parse($data['proposed_at']);
        DB::transaction(function () use ($employeeId, $data, $date, $proposed, $user, $attendance, $self): void {
            $owner = Employee::query()->lockForUpdate()->findOrFail($employeeId);
            abort_if($self && $owner->user_id !== $user->id, 403);
            if ($proposed->toDateString() !== $date) {
                $schedule = app(ScheduleResolver::class)->resolve($employeeId, $date);
                $window = $schedule ? app(PunchClassifier::class)->boundaries($date, $schedule) : null;
                if (! $window || ! $proposed->betweenIncluded($window['am_in_window_start'], $window['pm_out_window_end'])) {
                    throw ValidationException::withMessages(['proposed_at' => 'Use the work date or a time within the scheduled shift window.']);
                }
            }
            if (! isset($attendance->reviewSummary($owner, $date, $date)['flagged'][$date])) {
                throw ValidationException::withMessages(['work_date' => 'Choose a flagged attendance day.']);
            }
            if ($owner->attendanceCorrections()->where('work_date', $date)->where('slot', $data['slot'])
                ->whereIn('status', self::OPEN_STATUSES)->exists()) {
                throw ValidationException::withMessages(['slot' => 'This slot already has a pending request.']);
            }
            $entry = $owner->attendanceCorrections()->create([
                'correction_type' => 'slot_override',
                'work_date' => $date,
                'slot' => $data['slot'],
                'proposed_at' => $proposed->toDateTimeString(),
                'reason' => $data['reason'],
                'evidence' => $data['evidence'] ?? null,
                'filed_by' => $user->id,
                'status' => 'pending',
            ]);
            $this->audit($user, $entry, 'filed', null);
        });

        return to_route('hris.attendance-corrections.index', [
            'month' => substr($date, 0, 7),
            ...($self ? [] : ['employee' => $employeeId]),
        ]);
    }

    public function review(Request $request, AttendanceCorrection $correction, AttendanceRecomputer $attendance): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->is_active && $user->role?->name === 'HRMO Head', 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,return,reject'],
            'comment' => ['required_if:decision,return', 'nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($user, $data, $correction, $attendance): void {
            $owner = $correction->employee()->lockForUpdate()->firstOrFail();
            $entry = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);
            abort_if($entry->filed_by === $user->id || $owner->user_id === $user->id || $owner->id === $user->employee_id, 403);
            if (! in_array($entry->status, self::OPEN_STATUSES, true)) {
                throw ValidationException::withMessages(['decision' => 'This request has already been decided.']);
            }
            $status = match ($data['decision']) {
                'approve' => 'approved',
                'return' => 'returned',
                'reject' => 'rejected',
            };
            if ($status === 'approved' && (! $entry->slot || ! $entry->proposed_at || ! $entry->work_date)) {
                throw ValidationException::withMessages(['decision' => 'Return this older request and have it filed for a specific slot.']);
            }
            $entry->update([
                'status' => $status,
                'decision_comment' => $data['comment'] ?? null,
                ...($status === 'approved' ? ['approved_by' => $user->id, 'approved_at' => now()] : []),
            ]);
            if ($status === 'approved') {
                $day = $attendance->recomputeEmployeeDay($owner->id, $entry->work_date);
                $times = array_values(array_filter([$day->am_in_at, $day->am_out_at, $day->pm_in_at, $day->pm_out_at]));
                for ($i = 1; $i < count($times); $i++) {
                    if ($times[$i]->lt($times[$i - 1])) {
                        throw ValidationException::withMessages(['decision' => 'The proposed time puts the calculated slots out of order.']);
                    }
                }
            }
            $this->audit($user, $entry, $status, $entry->decision_comment);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance correction decision saved.']);

        return back();
    }

    private function linkedEmployee(User $user): Employee
    {
        $employee = $user->employee;
        abort_unless($employee && $employee->user_id === $user->id, 403);

        return $employee;
    }

    private function row(AttendanceCorrection $row, User $user): array
    {
        return [
            'id' => $row->id,
            'date' => $row->work_date ?? $row->attendanceRecord?->attendance_date ?? substr($row->proposed_time_in ?? '', 0, 10),
            'employee' => $row->employee?->employee_number,
            'slot' => $row->slot,
            'proposedAt' => $row->proposed_at,
            'legacyTimes' => array_values(array_filter([$row->proposed_time_in, $row->proposed_time_out])),
            'reason' => $row->reason,
            'evidence' => $row->evidence,
            'filer' => $row->filer?->name,
            'status' => $row->status,
            'comment' => $row->decision_comment,
            'canReview' => $user->role?->name === 'HRMO Head' && $row->filed_by !== $user->id
                && $row->employee_id !== $user->employee_id && $row->employee?->user_id !== $user->id
                && in_array($row->status, self::OPEN_STATUSES, true),
        ];
    }

    private function audit(User $user, AttendanceCorrection $entry, string $action, ?string $comment): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'attendance_correction.'.$action,
            'model_type' => AttendanceCorrection::class,
            'model_id' => $entry->id,
            'metadata' => json_encode(['employee_id' => $entry->employee_id, 'work_date' => $entry->work_date, 'slot' => $entry->slot, 'status' => $entry->status, 'comment' => $comment], JSON_THROW_ON_ERROR),
        ]);
    }
}
