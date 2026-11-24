<?php

namespace App\Attendance\Controllers;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Models\DepartmentSchedule;
use App\Attendance\Models\EmployeeScheduleOverride;
use App\Attendance\Services\AttendanceRecomputer;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleController
{
    public function __construct(
        private EmployeeDirectory $employees,
        private AttendanceRecomputer $attendance,
    ) {}

    public function index(Request $request, string $kind, int $owner)
    {
        return response()->json($this->schedules($kind, $owner)->latest('effective_from')->latest('id')->get());
    }

    public function store(Request $request, string $kind, int $owner)
    {
        return $this->save($request, $kind, $owner);
    }

    public function update(Request $request, string $kind, int $owner, int $schedule)
    {
        return $this->save($request, $kind, $owner, $schedule);
    }

    public function destroy(Request $request, string $kind, int $owner, int $schedule)
    {
        return $this->save($request, $kind, $owner, $schedule, delete: true);
    }

    private function schedules(string $kind, int $owner): Builder
    {
        if ($kind === 'department-schedules') {
            abort_unless($this->employees->departmentExists($owner), 404);

            return DepartmentSchedule::query()->withAttributes(['department_id' => $owner]);
        }

        abort_unless($this->employees->find($owner) !== null, 404);

        return EmployeeScheduleOverride::query()->withAttributes(['employee_id' => $owner]);
    }

    private function save(Request $request, string $kind, int $owner, ?int $id = null, bool $delete = false)
    {
        $query = $this->schedules($kind, $owner);
        $data = $delete ? [] : $this->validated($request, $kind === 'employee-overrides');

        $schedule = DB::transaction(function () use ($request, $query, $kind, $owner, $id, $delete, $data) {
            $employees = $kind === 'department-schedules'
                ? $this->employees->list(departmentId: $owner, forUpdate: true)
                : array_filter([$this->employees->find($owner, forUpdate: true)]);
            $schedule = $id === null ? $query->newModelInstance() : $query->lockForUpdate()->findOrFail($id);
            $old = $schedule->exists ? $schedule->attributesToArray() : null;
            $from = $schedule->effective_from?->toDateString() ?? $data['effective_from'];

            if ($delete) {
                $schedule->delete();
            } else {
                $schedule->fill($data)->save();
                $from = min($from, $data['effective_from']);
            }

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'attendance.schedule.'.($delete ? 'deleted' : ($old ? 'updated' : 'created')),
                'model_type' => $schedule::class,
                'model_id' => $schedule->id,
                'metadata' => json_encode(['old' => $old, 'new' => $delete ? null : $schedule->refresh()->attributesToArray()], JSON_THROW_ON_ERROR),
            ]);

            foreach ($employees as $employee) {
                $this->attendance->recompute($employee['id'], $from);
            }

            return $schedule;
        }, attempts: 3);

        if (! $request->expectsJson()) {
            return back();
        }

        return $delete ? response()->noContent() : response()->json($schedule, $id === null ? 201 : 200);
    }

    private function validated(Request $request, bool $override): array
    {
        $fields = ['am_in', 'am_out', 'pm_in', 'pm_out'];
        $data = $request->validate(array_fill_keys($fields, 'required|date_format:H:i') + [
            'grace_minutes' => 'required|integer|min:0|max:1440',
            'crosses_midnight' => 'required|boolean',
            'effective_from' => 'required|date_format:Y-m-d',
            'effective_to' => 'nullable|date_format:Y-m-d|after_or_equal:effective_from',
        ] + ($override ? ['reason' => 'required|string|max:255'] : []));

        $times = array_map(function (string $field) use ($data): int {
            [$hour, $minute] = explode(':', $data[$field]);

            return (int) $hour * 60 + (int) $minute;
        }, $fields);

        foreach ($times as $index => $time) {
            if ($index === 0) {
                continue;
            }
            $clockTime = $time + ($data['crosses_midnight'] && $time < $times[0] ? 1440 : 0);
            if ($clockTime <= $times[$index - 1]) {
                throw ValidationException::withMessages([$fields[$index] => 'Enter work times in shift order.']);
            }
            $times[$index] = $clockTime;
        }
        if ($data['crosses_midnight'] && $times[3] < 1440) {
            throw ValidationException::withMessages(['crosses_midnight' => 'At least one work time must fall after midnight.']);
        }

        return $data + ['effective_to' => null];
    }
}
