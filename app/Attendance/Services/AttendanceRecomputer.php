<?php

namespace App\Attendance\Services;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Models\AttendanceDaily;
use App\Models\AttendancePunch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class AttendanceRecomputer
{
    public function __construct(
        private EmployeeDirectory $employees,
        private ScheduleResolver $resolver,
        private PunchClassifier $classifier,
    ) {}

    /**
     * @return array{employee_ids:list<int>, work_dates:list<string>}
     */
    public function recompute(?int $employeeId = null, ?string $from = null, ?string $to = null): array
    {
        $employees = $employeeId === null
            ? $this->employees->list()
            : array_filter([$this->employees->find($employeeId)]);
        $workDates = [];

        foreach ($employees as $employee) {
            $punchQuery = $this->punchesFor($employee['biometric_id']);
            if ($from !== null) {
                $punchQuery->where('punched_at', '>=', Carbon::parse($from)->subDays($this->earlyDays())->startOfDay());
            }
            if ($to !== null) {
                $punchQuery->where('punched_at', '<', Carbon::parse($to)->addDays($this->lateDays() + 1)->startOfDay());
            }

            $windows = [];
            $dates = [];
            foreach ($punchQuery->distinct()->pluck('punched_at') as $timestamp) {
                $date = $this->workDateFor($employee['id'], (string) $timestamp, $windows);
                if (($from === null || $date >= $from) && ($to === null || $date <= $to)) {
                    $dates[$date] = $date;
                }
            }

            if ($from !== null && $to !== null) {
                $current = Carbon::parse($from)->startOfDay();
                $end = Carbon::parse($to)->startOfDay();
                while ($current->lte($end)) {
                    $dates[] = $current->toDateString();
                    $current->addDay();
                }
            }

            foreach (array_values(array_unique($dates)) as $workDate) {
                $workDates[$workDate] = $workDate;
                $this->recomputeEmployeeDay($employee['id'], $workDate);
            }
        }

        return [
            'employee_ids' => array_values(array_column($employees, 'id')),
            'work_dates' => array_values($workDates),
        ];
    }

    /**
     * @return array{employee_ids:list<int>,work_dates:list<string>,machine_ids:list<int>,unlinked_machine_ids:list<int>}
     */
    public function recomputeImport(array $rows): array
    {
        $byMachine = collect($rows)->groupBy('machine_user_id');
        $machineIds = $byMachine->keys()->map(fn ($id): int => (int) $id);
        $employees = $this->employees->list($machineIds->all());
        $workDates = [];

        foreach ($employees as $employee) {
            $windows = [];
            $dates = [];
            foreach ($byMachine[$employee['biometric_id']] as $punch) {
                $date = $this->workDateFor($employee['id'], $punch['punched_at'], $windows);
                $dates[$date] = $date;
            }
            foreach ($dates as $date) {
                $this->recomputeEmployeeDay($employee['id'], $date);
                $workDates[$date] = $date;
            }
        }

        return [
            'employee_ids' => array_column($employees, 'id'),
            'work_dates' => array_values($workDates),
            'machine_ids' => $machineIds->all(),
            'unlinked_machine_ids' => $machineIds->diff(array_column($employees, 'biometric_id'))->values()->all(),
        ];
    }

    public function recomputeEmployeeDay(int $employeeId, string $workDate): AttendanceDaily
    {
        $employee = $this->employees->find($employeeId)
            ?? throw (new ModelNotFoundException)->setModel('Employee', [$employeeId]);
        $schedule = $this->resolver->resolve($employeeId, $workDate);
        $windows = [$workDate => $schedule === null ? null : $this->classifier->boundaries($workDate, $schedule)];
        $bounds = $windows[$workDate];
        $start = Carbon::parse($workDate)->startOfDay();
        $end = $start->copy()->endOfDay();

        $rawPunches = $this->punchesFor($employee['biometric_id'])
            ->whereBetween('punched_at', [
                $start->min($bounds['am_in_window_start'] ?? $start),
                $end->max($bounds['pm_out_window_end'] ?? $end),
            ])
            ->orderBy('punched_at')
            ->orderBy('id')
            ->get(['punched_at'])
            ->filter(function (AttendancePunch $punch) use ($employeeId, $workDate, &$windows): bool {
                return $this->workDateFor($employeeId, (string) $punch->punched_at, $windows) === $workDate;
            })
            ->map(fn (AttendancePunch $punch): array => ['punched_at' => (string) $punch->punched_at])
            ->values()
            ->all();

        $preCheck = $this->precheckEmployeeDay($employeeId, $workDate);
        $result = $this->classifier->classify($rawPunches, $schedule, $workDate);
        $flags = array_merge($result['flags'], $preCheck);

        return AttendanceDaily::query()->updateOrCreate(
            ['employee_id' => $employeeId, 'work_date' => $workDate],
            [
                'am_in_at' => $result['am_in_at'],
                'am_out_at' => $result['am_out_at'],
                'pm_in_at' => $result['pm_in_at'],
                'pm_out_at' => $result['pm_out_at'],
                'late_minutes_am' => $result['late_minutes_am'],
                'late_minutes_pm' => $result['late_minutes_pm'],
                'undertime_minutes' => $result['undertime_minutes'],
                'status' => $result['status'],
                'flags' => $flags,
                'schedule_source' => $schedule['schedule_source'] ?? 'department',
                'schedule_id' => $schedule['schedule_id'] ?? null,
            ],
        );
    }

    private function punchesFor(?int $machineId): Builder
    {
        return AttendancePunch::query()->where(function (Builder $query) use ($machineId): void {
            if ($machineId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('machine_id', $machineId)->orWhere('machine_user_id', $machineId);
        });
    }

    private function workDateFor(int $employeeId, string $timestamp, array &$windows): string
    {
        $punch = Carbon::parse($timestamp);
        $workDate = $punch->toDateString();
        $closest = INF;

        // Assign a punch to one shift when the classification windows overlap.
        $earlyDays = $this->earlyDays();
        for ($offset = -$this->lateDays(); $offset <= $earlyDays; $offset++) {
            $date = $punch->copy()->addDays($offset)->toDateString();
            if (! array_key_exists($date, $windows)) {
                $schedule = $this->resolver->resolve($employeeId, $date);
                $windows[$date] = $schedule === null ? null : $this->classifier->boundaries($date, $schedule);
            }
            $bounds = $windows[$date];
            if ($bounds === null || ! $punch->betweenIncluded($bounds['am_in_window_start'], $bounds['pm_out_window_end'])) {
                continue;
            }

            $distance = max(
                0,
                $bounds['am_in_scheduled']->getTimestamp() - $punch->getTimestamp(),
                $punch->getTimestamp() - $bounds['pm_out_scheduled']->getTimestamp(),
            );
            if ($distance <= $closest) {
                $closest = $distance;
                $workDate = $date;
            }
        }

        return $workDate;
    }

    private function earlyDays(): int
    {
        return (int) ceil(max(0, config('attendance.classifier.am_in_hours_before')) / 24);
    }

    private function lateDays(): int
    {
        return 1 + (int) ceil(max(0, config('attendance.classifier.pm_out_hours_after')) / 24);
    }

    /**
     * Extension point for leave, travel orders, holidays, and approved corrections.
     *
     * @return array<string,mixed>
     */
    public function precheckEmployeeDay(int $employeeId, string $workDate): array
    {
        return [];
    }
}
