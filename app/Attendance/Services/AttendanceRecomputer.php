<?php

namespace App\Attendance\Services;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Models\AttendanceDaily;
use App\Attendance\Models\BiometricLink;
use App\Models\AttendanceCorrection;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
            $dates = AttendanceDaily::query()->where('employee_id', $employee['id'])
                ->when($from !== null, fn ($query) => $query->where('work_date', '>=', $from))
                ->when($to !== null, fn ($query) => $query->where('work_date', '<=', $to))
                ->pluck('work_date')->map(fn ($date) => $date->toDateString())->all();
            $dates = [...$dates, ...$this->recordedWorkDates($employee['id'], $from, $to)];

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
            'employee_ids' => array_column($employees, 'id'),
            'work_dates' => array_values($workDates),
        ];
    }

    /**
     * @param list<array{machine_user_id: int, punched_at: string}> $rows
     * @return array{employee_ids:list<int>,work_dates:list<string>,machine_ids:list<int>,unlinked_machine_ids:list<int>}
     */
    public function recomputeImport(array $rows): array
    {
        $byMachine = collect($rows)->groupBy('machine_user_id');
        $machineIds = $byMachine->keys()->map(fn ($id): int => (int) $id);
        $employees = collect($this->employees->list($machineIds->all()))->keyBy('biometric_id');
        $history = BiometricLink::historyAvailable()
            ? BiometricLink::query()->whereIntegerInRaw('biometric_id', $machineIds->all())->get()->groupBy('biometric_id')
            : collect();
        $workDates = [];
        $employeeDays = $unlinked = $windows = [];

        foreach ($byMachine as $machineId => $punches) {
            $links = $history->get($machineId);
            foreach ($punches as $punch) {
                $timestamp = $punch['punched_at'];
                $employeeId = $links === null
                    ? ($employees->get($machineId)['id'] ?? null)
                    : $links->first(fn (BiometricLink $link) => ($link->effective_from?->toDateTimeString() ?? '') <= $timestamp
                        && ($link->effective_to === null || $link->effective_to->toDateTimeString() > $timestamp))?->employee_id;
                if ($employeeId === null) {
                    $unlinked[$machineId] = (int) $machineId;

                    continue;
                }
                $windows[$employeeId] ??= [];
                $date = $this->workDateFor($employeeId, $timestamp, $windows[$employeeId]);
                $employeeDays[$employeeId][$date] = $date;
            }
        }
        foreach ($employeeDays as $employeeId => $dates) {
            foreach ($dates as $date) {
                $this->recomputeEmployeeDay($employeeId, $date);
                $workDates[$date] = $date;
            }
        }

        return [
            'employee_ids' => array_keys($employeeDays),
            'work_dates' => array_values($workDates),
            'machine_ids' => array_values($machineIds->all()),
            'unlinked_machine_ids' => array_values($unlinked),
        ];
    }

    public function recomputeEmployeeDay(int $employeeId, string $workDate): AttendanceDaily
    {
        $day = $this->employeeDay($employeeId, $workDate);

        return AttendanceDaily::query()->updateOrCreate(
            ['employee_id' => $employeeId, 'work_date' => $workDate],
            collect($day)->except(['punches', 'slots'])->all(),
        );
    }

    public function employeeDay(int $employeeId, string $workDate): array
    {
        if ($this->employees->find($employeeId) === null) {
            throw (new ModelNotFoundException)->setModel(Employee::class, [$employeeId]);
        }
        $schedule = $this->resolver->resolve($employeeId, $workDate);
        $windows = [$workDate => $schedule === null ? null : $this->classifier->boundaries($workDate, $schedule)];
        $bounds = $windows[$workDate];
        $start = Carbon::parse($workDate)->startOfDay();
        $end = $start->copy()->endOfDay();

        $rawPunches = AttendancePunch::query()->ownedBy($employeeId)
            ->whereBetween('punched_at', [
                $start->min($bounds['am_in_window_start'] ?? $start),
                $end->max($bounds['pm_out_window_end'] ?? $end),
            ])
            ->orderBy('punched_at')
            ->orderBy('id')
            ->get(['id', 'punched_at', 'machine_id', 'machine_user_id', 'device_id', 'punch_state', 'raw_status', 'verify_mode', 'work_code', 'raw_3', 'raw_4', 'raw_5', 'raw_6'])
            ->filter(function (AttendancePunch $punch) use ($employeeId, $workDate, &$windows): bool {
                return $this->workDateFor($employeeId, (string) $punch->punched_at, $windows) === $workDate;
            })
            ->values();

        $preCheck = $this->precheckEmployeeDay($employeeId, $workDate);
        $overrides = AttendanceCorrection::query()->where('employee_id', $employeeId)
            ->where('work_date', $workDate)->where('status', 'approved')
            ->whereNotNull('slot')->whereNotNull('proposed_at')->orderBy('id')
            ->pluck('proposed_at', 'slot')->all();
        $recordedSlots = [];
        if ($overrides !== []) {
            $record = AttendanceRecord::where('employee_id', $employeeId)->where('attendance_date', $workDate)->latest('id')->first();
            $recordedSlots = array_filter([
                'am_in' => $record?->time_in,
                'am_out' => $record?->am_out,
                'pm_in' => $record?->pm_in,
                'pm_out' => $record?->time_out,
            ]);
        }
        $result = $this->classifier->classify($rawPunches->toArray(), $schedule, $workDate, $overrides, $recordedSlots);
        if ($overrides !== []) {
            $result['flags']['correction_slots'] = array_keys($overrides);
        }
        $assignments = collect($result['punches'])->keyBy('punched_at');
        $result['flags'] = array_merge($result['flags'], $preCheck);
        $result['punches'] = $rawPunches->map(function (AttendancePunch $punch) use ($assignments): array {
            $assignment = $assignments->pull($punch->punched_at);

            return $punch->toArray() + [
                'slot' => $assignment['slot'] ?? null,
                'merged_duplicate' => $assignment === null,
            ];
        })->all();

        return $result + [
            'schedule_source' => $schedule['schedule_source'] ?? null,
            'schedule_id' => $schedule['schedule_id'] ?? null,
        ];
    }

    /** @return array{complete:int, flagged:array<string,string>} */
    public function reviewSummary(Employee $employee, string $from, string $to, ?Collection $daily = null): array
    {
        $manual = ($employee->relationLoaded('attendanceRecords') ? $employee->attendanceRecords
            : $employee->attendanceRecords()->whereBetween('attendance_date', [$from, $to])->latest('id')->get())
            ->unique('attendance_date')->keyBy('attendance_date');
        $stored = ($daily ?? AttendanceDaily::where('employee_id', $employee->id)->whereBetween('work_date', [$from, $to])->get())
            ->keyBy(fn (AttendanceDaily $day): string => $day->work_date->toDateString());
        $dates = array_unique([...$manual->keys(), ...$stored->filter(fn (AttendanceDaily $day) => $day->status === 'absent' || ! empty($day->flags['correction_slots']))->keys(),
            ...$this->recordedWorkDates($employee->id, $from, $to)]);
        $complete = 0;
        $flagged = [];
        foreach ($dates as $date) {
            $record = $manual->get($date);
            $calculated = $stored->get($date);
            if ($record && empty($calculated?->flags['correction_slots'])) {
                $status = str_replace([' ', '-'], '_', strtolower(trim($record->status)));
                if (in_array($status, ['leave', 'on_leave', 'holiday', 'rest_day', 'day_off'], true)) {
                    continue;
                }
                $status = $record->time_in && $record->time_out ? 'complete' : 'incomplete';
            } else {
                $status = $calculated?->status ?? $this->employeeDay($employee->id, $date)['status'];
            }
            if ($status === 'complete') {
                $complete++;
            } elseif (in_array($status, ['incomplete', 'absent', 'no_schedule'], true)) {
                $flagged[$date] = $status;
            }
        }
        ksort($flagged);

        return ['complete' => $complete, 'flagged' => $flagged];
    }

    /** @return list<string> */
    public function recordedWorkDates(int $employeeId, ?string $from, ?string $to): array
    {
        $punchQuery = AttendancePunch::query()->ownedBy($employeeId)
            ->when($from !== null, fn ($query) => $query->where('punched_at', '>=', Carbon::parse($from)->subDays($this->earlyDays())->startOfDay()))
            ->when($to !== null, fn ($query) => $query->where('punched_at', '<', Carbon::parse($to)->addDays($this->lateDays() + 1)->startOfDay()));
        $windows = [];
        $dates = [];
        foreach ($punchQuery->distinct()->pluck('punched_at') as $timestamp) {
            $date = $this->workDateFor($employeeId, (string) $timestamp, $windows);
            if (($from === null || $date >= $from) && ($to === null || $date <= $to)) {
                $dates[$date] = $date;
            }
        }

        return array_values($dates);
    }

    public function workDateFor(int $employeeId, string $timestamp, array &$windows): string
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

    public function earlyDays(): int
    {
        return (int) ceil(max(0, config('attendance.classifier.am_in_hours_before')) / 24);
    }

    public function lateDays(): int
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
