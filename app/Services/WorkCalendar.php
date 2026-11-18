<?php

namespace App\Services;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceSchedule;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class WorkCalendar
{
    private Collection $schedules;

    private Collection $holidays;

    private Collection $approvedLeave;

    private string $now;

    /** @param Collection<int, Employee> $employees */
    public function __construct(Collection $employees, string $from, string $until)
    {
        $scopeKeys = ['municipal'];
        foreach ($employees as $employee) {
            $scopeKeys[] = 'employee:'.$employee->id;
            if ($employee->department_id) {
                $scopeKeys[] = 'office:'.$employee->department_id;
            }
        }

        $this->schedules = AttendanceSchedule::query()->whereIn('scope_key', array_unique($scopeKeys))
            ->where('effective_from', '<=', $until)
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>=', $from))
            ->orderByDesc('effective_from')->get()->groupBy('scope_key');
        $this->holidays = AttendanceHoliday::query()->whereBetween('date', [$from, $until])->pluck('name', 'date');
        $this->approvedLeave = LeaveApplication::query()->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')->where('start_date', '<=', $until)->where('end_date', '>=', $from)
            ->get(['employee_id', 'start_date', 'end_date', 'days_requested', 'working_dates'])->groupBy('employee_id');
        $this->now = now('Asia/Manila')->format('Y-m-d H:i');
    }

    public function scheduleOn(Employee $employee, string $date): ?AttendanceSchedule
    {
        foreach (['employee:'.$employee->id, 'office:'.$employee->department_id, 'municipal'] as $scope) {
            $schedule = $this->schedules->get($scope)?->first(fn (AttendanceSchedule $rule) => $rule->effective_from <= $date
                && ($rule->effective_until === null || $rule->effective_until >= $date));
            if ($schedule !== null) {
                return $schedule;
            }
        }

        return null;
    }

    public function holidayOn(string $date): ?string
    {
        return $this->holidays->get($date);
    }

    public function approvedLeaveOn(Employee $employee, string $date): bool
    {
        return $this->approvedLeave->get($employee->id, collect())->contains(function (LeaveApplication $leave) use ($date): bool {
            if ($leave->working_dates !== null) {
                return in_array($date, $leave->working_dates, true);
            }

            // A legacy request only identifies individual days if it covers its entire date range.
            return $leave->start_date <= $date && $date <= $leave->end_date
                && (float) $leave->days_requested === Date::parse($leave->start_date)->diffInDays(Date::parse($leave->end_date)) + 1;
        });
    }

    public function isWorkday(AttendanceSchedule $schedule, string $date): bool
    {
        return in_array(Date::parse($date)->dayOfWeekIso, $schedule->workdays, true);
    }

    public function hasEnded(Employee $employee, AttendanceSchedule $schedule, string $date): bool
    {
        return in_array(strtolower(trim($employee->employment_status)), ['active', 'on leave', 'on_leave'], true)
            && ($employee->date_hired === null || $date >= $employee->date_hired->toDateString())
            && $this->shiftHasEnded($schedule, $date);
    }

    public function shiftHasEnded(AttendanceSchedule $schedule, string $date): bool
    {
        return $date.' '.$schedule->pm_out <= $this->now;
    }

    /** @return list<string>|null */
    public function workingDates(Employee $employee, string $from, string $until): ?array
    {
        $workingDates = [];
        for ($day = Date::parse($from); $day->toDateString() <= $until; $day = $day->addDay()) {
            $date = $day->toDateString();
            if ($this->holidayOn($date) !== null) {
                continue;
            }
            $schedule = $this->scheduleOn($employee, $date);
            if ($schedule === null) {
                return null;
            }
            if ($this->isWorkday($schedule, $date)) {
                $workingDates[] = $date;
            }
        }

        return $workingDates;
    }
}
