<?php

namespace App\Attendance\Services;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Models\DepartmentSchedule;
use App\Attendance\Models\EmployeeScheduleOverride;
use Illuminate\Support\Carbon;

class ScheduleResolver
{
    public function __construct(private EmployeeDirectory $employees) {}

    /**
     * @return array{
     *     schedule_source:'override'|'department',
     *     schedule_id:int,
     *     am_in:string,
     *     am_out:string,
     *     pm_in:string,
     *     pm_out:string,
     *     grace_minutes:int,
     *     crosses_midnight:bool
     * }|null
     */
    public function resolve(int $employeeId, string $date): ?array
    {
        $workDate = Carbon::parse($date)->toDateString();

        $override = EmployeeScheduleOverride::query()
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', $workDate)
            ->where(function ($query) use ($workDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $workDate);
            })
            ->latest('effective_from')
            ->latest('id')
            ->first();

        if ($override) {
            return $this->payload('override', $override);
        }

        $employee = $this->employees->find($employeeId);
        $departmentId = $employee['department_id'] ?? null;
        if ($departmentId === null) {
            return null;
        }

        $department = DepartmentSchedule::query()
            ->where('department_id', $departmentId)
            ->where('effective_from', '<=', $workDate)
            ->where(function ($query) use ($workDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $workDate);
            })
            ->latest('effective_from')
            ->latest('id')
            ->first();

        return $department ? $this->payload('department', $department) : null;
    }

    /**
     * @return array{
     *     schedule_source:'override'|'department',
     *     schedule_id:int,
     *     am_in:string,
     *     am_out:string,
     *     pm_in:string,
     *     pm_out:string,
     *     grace_minutes:int,
     *     crosses_midnight:bool
     * }
     */
    private function payload(string $source, DepartmentSchedule|EmployeeScheduleOverride $schedule): array
    {
        $scheduleSource = $source === 'override' ? 'override' : 'department';

        return [
            'schedule_source' => $scheduleSource,
            'schedule_id' => (int) $schedule->id,
            'am_in' => substr((string) $schedule->am_in, 0, 5),
            'am_out' => substr((string) $schedule->am_out, 0, 5),
            'pm_in' => substr((string) $schedule->pm_in, 0, 5),
            'pm_out' => substr((string) $schedule->pm_out, 0, 5),
            'grace_minutes' => (int) $schedule->grace_minutes,
            'crosses_midnight' => (bool) $schedule->crosses_midnight,
        ];
    }
}
