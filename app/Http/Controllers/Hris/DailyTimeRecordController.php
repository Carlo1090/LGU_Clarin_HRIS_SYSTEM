<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\WorkCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DailyTimeRecordController extends Controller
{
    public function __invoke(Request $request, Employee $employee): Response|JsonResponse
    {
        $user = $request->user();
        $selfService = $user->role?->name === 'Employee';

        if ($selfService) {
            abort_unless($user->employee_id === $employee->id && $employee->user_id === $user->id, 403);
        } else {
            Gate::authorize('view-hris-resource', 'attendance');
            if ($user->role?->name === 'Department Head') {
                $departmentId = $user->employee?->department_id;
                abort_unless($departmentId && $departmentId === $employee->department_id, 403);
            }
        }

        $filters = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
            'period' => ['sometimes', 'required', 'in:full,first,second'],
        ]);

        $attendance = $employee->attendanceRecords();
        $punches = $employee->attendancePunches();
        $month = $filters['month'] ?? null;
        $period = $filters['period'] ?? 'full';
        $latestDate = null;
        if ($month === null) {
            $lastPunch = (clone $punches)->max('punched_at');
            $latestDate = max((clone $attendance)->max('attendance_date'), $lastPunch ? substr($lastPunch, 0, 10) : null);
        }
        $calendar = Date::parse($month ? $month.'-01' : ($latestDate ?? now()))->startOfMonth();
        $firstDay = $period === 'second' ? 16 : 1;
        $lastDay = $period === 'first' ? 15 : $calendar->daysInMonth;
        $periodLabel = $period === 'full'
            ? $calendar->format('F Y')
            : $calendar->format('F')." {$firstDay}–{$lastDay}, ".$calendar->year;
        $startDate = $calendar->day($firstDay)->toDateString();
        $endDate = $calendar->day($lastDay)->toDateString();
        $workCalendar = new WorkCalendar(collect([$employee]), $startDate, $endDate);
        $records = $attendance
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->latest('id')
            ->get(['attendance_date', 'time_in', 'time_out', 'status'])
            ->unique('attendance_date')
            ->keyBy(fn (AttendanceRecord $record) => (int) substr($record->attendance_date, -2))
            ->map(fn (AttendanceRecord $record) => [
                'amIn' => $record->time_in ? Date::parse($record->time_in)->format('H:i') : null,
                'pmOut' => $record->time_out ? Date::parse($record->time_out)->format('H:i') : null,
                'remarks' => $record->status === 'present' ? null : ucfirst(str_replace('_', ' ', $record->status)),
            ]);
        $manualDays = $records->keys();

        $punchTimes = $punches->whereBetween('punched_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderBy('punched_at')->pluck('punched_at')
            ->groupBy(fn (string $time) => (int) substr($time, 8, 2))
            ->map(fn ($times) => $times->map(fn (string $time) => substr($time, 11))->all());
        foreach ($punchTimes as $day => $times) {
            if ($records->has($day)) {
                continue;
            }
            $count = count($times);
            // Four ordered punches fill the four slots. Other counts need HR review.
            $records[$day] = $count === AttendancePunch::DTR_PUNCH_COUNT
                ? array_combine(['amIn', 'amOut', 'pmIn', 'pmOut'], array_map(fn (string $time) => substr($time, 0, 5), $times))
                : ['remarks' => "Review {$count} punch(es)"];
        }

        $officialHours = [];
        for ($day = $firstDay; $day <= $lastDay; $day++) {
            $date = $calendar->day($day)->toDateString();
            $schedule = $workCalendar->scheduleOn($employee, $date);
            $holiday = $workCalendar->holidayOn($date);
            $scheduled = $schedule !== null && $workCalendar->isWorkday($schedule, $date);
            if ($scheduled) {
                $officialHours[$schedule->hours()] = true;
            }
            if ($holiday !== null || ($schedule !== null && ! $scheduled) || $workCalendar->approvedLeaveOn($employee, $date)) {
                $remark = $holiday ? 'Holiday · '.$holiday : ($schedule !== null && ! $scheduled ? 'Rest day' : 'Approved leave');
                $row = $records->get($day, []);
                $row['remarks'] = empty($row['remarks']) ? $remark : $row['remarks'].' · '.$remark;
                $records[$day] = $row;

                continue;
            }
            if (! $scheduled || ! $workCalendar->hasEnded($employee, $schedule, $date)) {
                continue;
            }
            if (! $records->has($day)) {
                $records[$day] = ['remarks' => 'Missing log'];
            } elseif (! $manualDays->contains($day) && $punchTimes->has($day)) {
                $row = $records[$day];
                $variance = $schedule->variances($punchTimes[$day]);
                if ($variance !== null) {
                    if ($variance['undertime'] > 0) {
                        $row['undertimeHours'] = intdiv($variance['undertime'], 60);
                        $row['undertimeMinutes'] = $variance['undertime'] % 60;
                    }
                    $row['remarks'] = $variance['late'] > 0 ? "Late {$variance['late']} min" : null;
                } elseif (count($punchTimes[$day]) === AttendancePunch::DTR_PUNCH_COUNT) {
                    $row = ['remarks' => 'Review punch order'];
                }
                $records[$day] = $row;
            }
        }

        $givenNames = implode(' ', array_filter([$employee->first_name, $employee->middle_name, $employee->suffix]));

        $preview = [
            'name' => "{$employee->last_name}, {$givenNames}",
            'month' => $calendar->format('Y-m'),
            'period' => $period,
            'periodLabel' => $periodLabel,
            'officialHours' => count($officialHours) > 1 ? 'Varies by workday' : array_key_first($officialHours),
            'records' => (object) $records->all(),
            'punchTimes' => (object) $punchTimes->all(),
            'previewUrl' => route('hris.attendance.dtr', $employee, false),
            'backUrl' => $selfService
                ? route('hris.portal', 'my-attendance', false)
                : route('hris.resources.index', 'attendance', false),
        ];

        return $request->wantsJson()
            ? response()->json($preview)->header('Cache-Control', 'private, no-store')->header('Vary', 'Accept')
            : Inertia::render('hris/daily-time-record', $preview);
    }

    public function demo(Request $request): Response|JsonResponse
    {
        $selfService = $request->user()->role?->name === 'Employee';
        if (! $selfService) {
            Gate::authorize('view-hris-resource', 'attendance');
        }

        $preview = [
            'demo' => true,
            'name' => 'Reyes, Alex M.',
            'periodLabel' => 'October 16–31, 2026',
            'officialHours' => '08:00–12:00 / 13:00–17:00',
            'records' => [
                17 => ['remarks' => 'Saturday'],
                18 => ['remarks' => 'Sunday'],
                19 => ['amIn' => '07:55', 'amOut' => '12:02', 'pmIn' => '12:58', 'pmOut' => '17:05'],
                20 => ['amIn' => '07:57', 'amOut' => '12:00', 'pmIn' => '12:59', 'pmOut' => '16:40', 'undertimeHours' => 0, 'undertimeMinutes' => 20],
                21 => ['amIn' => '07:56', 'amOut' => '12:00', 'remarks' => 'PM vacation leave'],
                22 => ['remarks' => 'Sick leave'],
            ],
            'backUrl' => $selfService
                ? route('hris.portal', 'my-attendance', false)
                : route('hris.resources.index', 'attendance', false),
        ];

        return $request->wantsJson()
            ? response()->json($preview)->header('Cache-Control', 'private, no-store')->header('Vary', 'Accept')
            : Inertia::render('hris/daily-time-record', $preview);
    }
}
