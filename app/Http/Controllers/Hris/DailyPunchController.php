<?php

namespace App\Http\Controllers\Hris;

use App\Models\AttendancePunch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendancePunchDeriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @phpstan-type SlotPunch array{id: int, time: string, is_duplicate: bool, primary: bool, slot: string}
 * @phpstan-type RawPunch array{id: int, punched_at: string, punch_state: int|null, verify_mode: int|null, verify_label: string, device_id: int|null, work_code: int|null, derived_slot: string|null, is_duplicate: bool, flags: array<string, bool|string>}
 * @phpstan-type DailyRow array{
 *     key: string,
 *     machine_user_id: int,
 *     date: string,
 *     employee_id: int|null,
 *     employee_name: string|null,
 *     employee_number: string|null,
 *     department_name: string|null,
 *     slots: array{AM_IN: list<SlotPunch>, LUNCH_OUT: list<SlotPunch>, PM_IN: list<SlotPunch>, PM_OUT: list<SlotPunch>, OT_IN: list<SlotPunch>, OT_OUT: list<SlotPunch>},
 *     punches: list<RawPunch>,
 *     flags: array{missing: bool, missing_slots: list<string>, wrong_button: bool, duplicate: bool, pin_verify: bool, unlinked: bool},
 *     flag_labels: list<string>
 * }
 */
class DailyPunchController
{
    private const SLOT_NAMES = ['AM_IN', 'LUNCH_OUT', 'PM_IN', 'PM_OUT', 'OT_IN', 'OT_OUT'];

    public function index(Request $request): Response
    {
        $data = $this->pageData($request);
        unset($data['allRows']);

        return Inertia::render('hris/daily-punches', $data);
    }

    private function dateFromGroupKey(string $key): string
    {
        $parts = explode('|', $key);

        return $parts[count($parts) - 1];
    }

    private function linkedEmployee(AttendancePunch $punch): ?Employee
    {
        $employee = $punch->getRelationValue('employee');

        return $employee instanceof Employee ? $employee : null;
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->pageData($request);
        $xml = $this->workbook($data['allRows']);
        $filename = 'attendance-punches-'.$data['filters']['start_date'].'-to-'.$data['filters']['end_date'].'.xml';

        return response()->streamDownload(
            static fn () => print $xml,
            $filename,
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }

    /**
     * @return array{
     *     rows: array{data: list<DailyRow>, current_page: int, last_page: int, total: int, links: list<array{url: string|null, label: string, active: bool}>},
     *     allRows: list<DailyRow>,
     *     filters: array{start_date: string, end_date: string, employee: int|null, department: int|null, flags_only: string|null},
     *     employees: Collection<int, array{id: int, name: string}>,
     *     departments: Collection<int, array{id: int, name: string}>,
     *     canManage: bool,
     *     timezone: string
     * }
     */
    private function pageData(Request $request): array
    {
        $user = $request->user();
        Gate::authorize('view-hris-resource', 'attendance');
        $input = $request->validate([
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d', 'regex:/^[1-9][0-9]{3}-/'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d', 'regex:/^[1-9][0-9]{3}-/'],
            'employee' => ['sometimes', 'integer', 'min:1', 'exists:employees,id'],
            'department' => ['sometimes', 'integer', 'min:1', 'exists:departments,id'],
            'flags_only' => ['sometimes', 'in:missing,wrong_button,duplicate,pin_verify,unlinked'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        if (isset($input['start_date'], $input['end_date']) && $input['start_date'] > $input['end_date']) {
            throw ValidationException::withMessages(['end_date' => 'End date must be on or after the start date.']);
        }

        $latest = AttendancePunch::query()->max('punched_at');
        $fallbackDate = $latest === null ? now(config('attendance.timezone', 'Asia/Manila'))->toDateString() : substr($latest, 0, 10);
        $startDate = isset($input['start_date']) ? (string) $input['start_date'] : $fallbackDate;
        $endDate = isset($input['end_date']) ? (string) $input['end_date'] : $fallbackDate;
        $headDepartment = $user->role?->name === 'Department Head' ? $user->employee?->department_id : null;
        abort_if($user->role?->name === 'Department Head' && $headDepartment === null, 403);

        $query = AttendancePunch::query()
            ->with(['employee.department'])
            ->whereBetween('punched_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->when(isset($input['employee']), fn (Builder $builder) => $builder->whereHas('employee', fn (Builder $employee) => $employee->whereKey($input['employee'])))
            ->when(isset($input['department']), fn (Builder $builder) => $builder->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', $input['department'])))
            ->when($headDepartment !== null, fn (Builder $builder) => $builder->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', $headDepartment)))
            ->orderBy('machine_user_id')->orderBy('punched_at')->orderBy('id');
        $punches = $query->get();
        $groups = $punches->groupBy(fn (AttendancePunch $punch): string => $punch->machine_user_id.'|'.substr($punch->punched_at, 0, 10));
        $calendar = $this->calendarContext($groups);
        $deriver = app(AttendancePunchDeriver::class);
        $rows = $groups->map(function (Collection $dayPunches, string $key) use ($calendar, $deriver): array {
            [$machineUserId, $date] = explode('|', $key, 2);
            $first = $dayPunches->first();
            $employee = $this->linkedEmployee($first);
            $slots = [
                'AM_IN' => [],
                'LUNCH_OUT' => [],
                'PM_IN' => [],
                'PM_OUT' => [],
                'OT_IN' => [],
                'OT_OUT' => [],
            ];
            $allPunches = [];
            $wrongPmIn = false;
            $flags = [
                'missing' => false,
                'wrong_button' => false,
                'duplicate' => false,
                'pin_verify' => false,
                'unlinked' => $employee === null,
            ];

            foreach ($dayPunches as $punch) {
                $state = $punch->punch_state ?? $punch->raw_4;
                $verifyMode = $punch->verify_mode ?? $punch->raw_5;
                $slot = $punch->derived_slot ?? AttendancePunchDeriver::slotForState($state);
                $punchFlags = $deriver->flagsFor($state, $verifyMode, $punch->punched_at);
                $expected = $deriver->expectedSlot($punch->punched_at);
                if ($expected === 'PM_IN' && $slot !== 'PM_IN') {
                    $wrongPmIn = true;
                }
                $wrong = (bool) ($punchFlags['suspected_wrong_button'] ?? false);
                $duplicate = (bool) $punch->is_duplicate;
                $pin = $verifyMode === 0;
                $flags['wrong_button'] = $flags['wrong_button'] || $wrong;
                $flags['duplicate'] = $flags['duplicate'] || $duplicate;
                $flags['pin_verify'] = $flags['pin_verify'] || $pin;
                if (isset($slots[$slot])) {
                    $slots[$slot][] = [
                        'id' => $punch->id,
                        'time' => substr($punch->punched_at, 11),
                        'is_duplicate' => $duplicate,
                        'primary' => false,
                        'slot' => $slot,
                    ];
                }
                $allPunches[] = [
                    'id' => $punch->id,
                    'punched_at' => $punch->punched_at,
                    'punch_state' => $state,
                    'verify_mode' => $verifyMode,
                    'verify_label' => $verifyMode === 1 ? 'Fingerprint' : ($verifyMode === 0 ? 'PIN' : 'Mode '.$verifyMode),
                    'device_id' => $punch->device_id ?? $punch->raw_3,
                    'work_code' => $punch->work_code ?? $punch->raw_6,
                    'derived_slot' => $slot,
                    'is_duplicate' => $duplicate,
                    'flags' => $punchFlags,
                ];
            }

            $flags['missing_slots'] = [];
            foreach (['AM_IN', 'LUNCH_OUT', 'PM_IN', 'PM_OUT'] as $required) {
                if ($slots[$required] === [] && ! ($required === 'PM_IN' && $wrongPmIn)) {
                    $flags['missing_slots'][] = $required;
                }
            }
            $flags['missing'] = $flags['missing_slots'] !== [];

            $keyedFlags = [];
            foreach ($flags['missing_slots'] as $missingSlot) {
                $keyedFlags[] = 'Missing '.$missingSlot;
            }
            foreach ([
                'wrong_button' => 'Suspected wrong button',
                'duplicate' => 'Duplicate punch',
                'pin_verify' => 'PIN verification',
                'unlinked' => 'Unlinked machine ID',
            ] as $flag => $label) {
                if ($flags[$flag]) {
                    $keyedFlags[] = $label;
                }
            }
            $calendarFlags = [
                ...($calendar[0][$date] ?? []),
                ...($employee === null ? [] : ($calendar[$employee->id][$date] ?? [])),
            ];
            $keyedFlags = [...$keyedFlags, ...$calendarFlags];
            foreach ($slots as $slot => &$slotPunches) {
                foreach ($slotPunches as $index => &$slotPunch) {
                    $slotPunch['primary'] = $index === 0;
                }
                unset($slotPunch);
            }
            unset($slotPunches);

            return [
                'key' => $machineUserId.'-'.$date,
                'machine_user_id' => (int) $machineUserId,
                'date' => $date,
                'employee_id' => $employee?->id,
                'employee_name' => $employee ? "{$employee->last_name}, {$employee->first_name}" : null,
                'employee_number' => $employee?->employee_number,
                'department_name' => $employee?->department?->name,
                'slots' => $slots,
                'punches' => $allPunches,
                'flags' => $flags,
                'flag_labels' => $keyedFlags,
            ];
        })->values();

        if (($input['flags_only'] ?? null) !== null) {
            $filter = $input['flags_only'];
            $rows = $rows->filter(fn (array $row): bool => match ($filter) {
                'unlinked' => $row['flags']['unlinked'],
                'missing' => $row['flags']['missing'],
                default => (bool) $row['flags'][$filter],
            })->values();
        }

        $page = (int) ($input['page'] ?? 1);
        $perPage = 25;
        $lastPage = max(1, (int) ceil($rows->count() / $perPage));
        $firstLink = max(1, $page - 2);
        $lastLink = min($lastPage, $page + 2);
        $pageUrl = static function (int $number) use ($request): string {
            $query = [...$request->query(), 'page' => $number];

            return $request->url().'?'.http_build_query($query);
        };
        $links = [[
            'url' => $page > 1 ? $pageUrl($page - 1) : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ]];
        for ($number = $firstLink; $number <= $lastLink; $number++) {
            $links[] = ['url' => $pageUrl($number), 'label' => (string) $number, 'active' => $number === $page];
        }
        $links[] = [
            'url' => $page < $lastPage ? $pageUrl($page + 1) : null,
            'label' => 'Next &raquo;',
            'active' => false,
        ];
        $paginated = [
            'data' => array_values($rows->slice(($page - 1) * $perPage, $perPage)->all()),
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $rows->count(),
            'links' => $links,
        ];

        $departments = Department::query()
            ->when($headDepartment !== null, fn (Builder $builder) => $builder->whereKey($headDepartment))
            ->orderBy('name')->get(['id', 'name'])->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
            ]);
        $employees = Employee::query()
            ->whereNotNull('biometric_id')
            ->when($headDepartment !== null, fn (Builder $builder) => $builder->where('department_id', $headDepartment))
            ->when(isset($input['department']), fn (Builder $builder) => $builder->where('department_id', $input['department']))
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'name' => "{$employee->last_name}, {$employee->first_name} ({$employee->employee_number})",
            ]);

        $data = [
            'rows' => $paginated,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'employee' => isset($input['employee']) ? (int) $input['employee'] : null,
                'department' => isset($input['department']) ? (int) $input['department'] : $headDepartment,
                'flags_only' => isset($input['flags_only']) ? (string) $input['flags_only'] : null,
            ],
            'employees' => $employees,
            'departments' => $departments,
            'canManage' => $user->can('manage-hris-resource', 'attendance'),
            'timezone' => (string) config('attendance.timezone', 'Asia/Manila'),
        ];
        $data['allRows'] = array_values($rows->all());

        return $data;
    }

    /**
     * @param  Collection<array-key, EloquentCollection<int, AttendancePunch>>  $groups
     * @return array<int, array<string, list<string>>>
     */
    private function calendarContext(Collection $groups): array
    {
        $employeeIds = $groups->flatten(1)->map(fn (AttendancePunch $punch): ?int => $this->linkedEmployee($punch)?->id)->filter()->unique()->values();
        $dates = $groups->keys()->map(fn (string|int $key): string => $this->dateFromGroupKey((string) $key))->unique()->values();
        if ($employeeIds->isEmpty() || $dates->isEmpty()) {
            return [];
        }
        $start = $dates->min();
        $end = $dates->max();
        $result = [];

        foreach (DB::table('attendance_holidays')->whereBetween('date', [$start, $end])->get(['date', 'name']) as $holiday) {
            $result[0][$holiday->date][] = 'Holiday: '.$holiday->name;
        }

        foreach (DB::table('leave_applications')->whereIn('employee_id', $employeeIds)->where('status', 'approved')
            ->where('start_date', '<=', $end)->where('end_date', '>=', $start)->get(['employee_id', 'start_date', 'end_date']) as $leave) {
            foreach ($dates as $date) {
                if ($leave->start_date <= $date && $leave->end_date >= $date) {
                    $result[$leave->employee_id][$date][] = 'Approved leave';
                }
            }
        }

        foreach (DB::table('travel_orders')->whereIn('employee_id', $employeeIds)->where('status', 'approved')
            ->where('departure_date', '<=', $end)->where('return_date', '>=', $start)->get(['employee_id', 'departure_date', 'return_date']) as $travel) {
            foreach ($dates as $date) {
                if ($travel->departure_date <= $date && $travel->return_date >= $date) {
                    $result[$travel->employee_id][$date][] = 'Approved travel order';
                }
            }
        }

        $departmentIds = $groups->flatten(1)->map(fn (AttendancePunch $punch): ?int => $this->linkedEmployee($punch)?->department_id)->filter()->unique();
        $schedules = DB::table('attendance_schedules')
            ->where('effective_from', '<=', $end)
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>=', $start))
            ->where(function ($query) use ($employeeIds, $departmentIds): void {
                $query->whereIn('employee_id', $employeeIds);
                if ($departmentIds->isNotEmpty()) {
                    $query->orWhereIn('department_id', $departmentIds);
                }
            })->orderBy('effective_from')->get();

        foreach ($groups as $key => $punches) {
            $employee = $this->linkedEmployee($punches->first());
            if ($employee === null) {
                continue;
            }
            $date = $this->dateFromGroupKey($key);
            $applicable = $schedules->filter(fn ($schedule): bool => $schedule->effective_from <= $date
                && ($schedule->effective_until === null || $schedule->effective_until >= $date)
                && ($schedule->employee_id === $employee->id || ($schedule->employee_id === null && $schedule->department_id === $employee->department_id)))
                ->sortByDesc(fn ($schedule): string => ($schedule->employee_id === $employee->id ? '1' : '0').$schedule->effective_from)
                ->first();
            if ($applicable === null) {
                continue;
            }
            $workdays = json_decode($applicable->workdays, true);
            if (! is_array($workdays)) {
                continue;
            }
            $localDate = Date::parse($date);
            $day = strtolower($localDate->format('l'));
            $dayNumber = (string) $localDate->dayOfWeek;
            $dayNames = array_map(static fn ($value): string => strtolower((string) $value), $workdays);
            $dayNumbers = array_map('strval', $workdays);
            $worksToday = in_array($day, $dayNames, true)
                || in_array($dayNumber, $dayNumbers, true)
                || in_array($localDate->dayOfWeek, $workdays, true);
            if (! $worksToday) {
                $result[$employee->id][$date][] = 'Scheduled non-working day';
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function workbook(array $rows): string
    {
        $daily = [['Employee', 'Employee Number', 'Department', 'Date', 'Morning IN', 'Lunch/Morning OUT', 'Afternoon IN', 'Afternoon/Evening OUT', 'OT IN', 'OT OUT', 'Flags']];
        $raw = [['Employee', 'Employee Number', 'Machine User ID', 'Date and Time', 'Punch State', 'Verify Mode', 'Device ID', 'Work Code', 'Derived Slot', 'Duplicate', 'Flags']];

        foreach ($rows as $row) {
            $slotValues = [];
            foreach (self::SLOT_NAMES as $slot) {
                $slotValues[$slot] = implode(', ', array_column($row['slots'][$slot], 'time'));
            }
            $daily[] = [
                $row['employee_name'] ?? 'Unlinked ID '.$row['machine_user_id'],
                $row['employee_number'] ?? '',
                $row['department_name'] ?? '',
                $row['date'],
                $slotValues['AM_IN'],
                $slotValues['LUNCH_OUT'],
                $slotValues['PM_IN'],
                $slotValues['PM_OUT'],
                $slotValues['OT_IN'],
                $slotValues['OT_OUT'],
                implode('; ', $row['flag_labels']),
            ];
            foreach ($row['punches'] as $punch) {
                $raw[] = [
                    $row['employee_name'] ?? 'Unlinked ID '.$row['machine_user_id'],
                    $row['employee_number'] ?? '',
                    (string) $row['machine_user_id'],
                    $punch['punched_at'],
                    (string) ($punch['punch_state'] ?? ''),
                    $punch['verify_label'],
                    (string) ($punch['device_id'] ?? ''),
                    (string) ($punch['work_code'] ?? ''),
                    (string) ($punch['derived_slot'] ?? ''),
                    $punch['is_duplicate'] ? 'Yes' : 'No',
                    implode(', ', array_map(
                        static fn (string|int $key, mixed $value): string => is_bool($value) ? (string) $key : $key.': '.$value,
                        array_keys($punch['flags']),
                        array_values($punch['flags']),
                    )),
                ];
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .$this->worksheet('Daily Punches', $daily)
            .$this->worksheet('Raw Punches', $raw)
            .'</Workbook>';
    }

    /** @param list<list<string>> $rows */
    private function worksheet(string $name, array $rows): string
    {
        $xml = '<Worksheet ss:Name="'.$this->xml($name).'"><Table>';
        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $value) {
                $xml .= '<Cell><Data ss:Type="String">'.$this->xml((string) $value).'</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        return $xml.'</Table></Worksheet>';
    }

    private function xml(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
