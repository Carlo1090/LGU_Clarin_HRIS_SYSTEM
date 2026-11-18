<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DailyTimeRecordController extends Controller
{
    public function __invoke(Request $request, Employee $employee): Response
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
        $month = $filters['month'] ?? null;
        $period = $filters['period'] ?? 'full';
        $latestDate = $month === null ? (clone $attendance)->max('attendance_date') : null;
        $calendar = Date::parse($month ? $month.'-01' : ($latestDate ?? now()))->startOfMonth();
        $firstDay = $period === 'second' ? 16 : 1;
        $lastDay = $period === 'first' ? 15 : $calendar->daysInMonth;
        $periodLabel = $period === 'full'
            ? $calendar->format('F Y')
            : $calendar->format('F')." {$firstDay}–{$lastDay}, ".$calendar->year;
        $records = $attendance
            ->whereBetween('attendance_date', [$calendar->day($firstDay)->toDateString(), $calendar->day($lastDay)->toDateString()])
            ->latest('id')
            ->get(['attendance_date', 'time_in', 'time_out', 'status'])
            ->unique('attendance_date')
            ->keyBy(fn (AttendanceRecord $record) => (int) substr($record->attendance_date, -2))
            ->map(fn (AttendanceRecord $record) => [
                'amIn' => $record->time_in ? Date::parse($record->time_in)->format('H:i') : null,
                'pmOut' => $record->time_out ? Date::parse($record->time_out)->format('H:i') : null,
                'remarks' => $record->status === 'present' ? null : ucfirst(str_replace('_', ' ', $record->status)),
            ]);

        $givenNames = implode(' ', array_filter([$employee->first_name, $employee->middle_name, $employee->suffix]));

        return Inertia::render('hris/daily-time-record', [
            'name' => "{$employee->last_name}, {$givenNames}",
            'month' => $calendar->format('Y-m'),
            'period' => $period,
            'periodLabel' => $periodLabel,
            'records' => (object) $records->all(),
            'previewUrl' => route('hris.attendance.dtr', $employee, false),
            'backUrl' => $selfService
                ? route('hris.portal', 'my-attendance', false)
                : route('hris.resources.index', 'attendance', false),
        ]);
    }

    public function demo(Request $request): Response
    {
        $selfService = $request->user()->role?->name === 'Employee';
        if (! $selfService) {
            Gate::authorize('view-hris-resource', 'attendance');
        }

        return Inertia::render('hris/daily-time-record', [
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
        ]);
    }
}
