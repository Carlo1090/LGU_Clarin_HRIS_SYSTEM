<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DailyTimeRecordController extends Controller
{
    public function confirmReview(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'am_in' => ['required', 'date_format:H:i'],
            'am_out' => ['required', 'date_format:H:i'],
            'pm_in' => ['required', 'date_format:H:i'],
            'pm_out' => ['required', 'date_format:H:i'],
        ]);

        $date = Date::parse($data['attendance_date'])->toDateString();
        $amIn = Date::parse($date.' '.$data['am_in']);
        $amOut = Date::parse($date.' '.$data['am_out']);
        $pmIn = Date::parse($date.' '.$data['pm_in']);
        $pmOut = Date::parse($date.' '.$data['pm_out']);

        if ($amIn->gt($amOut) || $amOut->gt($pmIn) || $pmIn->gt($pmOut)) {
            throw ValidationException::withMessages([
                'pm_in' => 'Use a chronological sequence: AM In, AM Out, PM In, PM Out.',
            ]);
        }

        DB::transaction(function () use ($employee, $date, $amIn, $amOut, $pmIn, $pmOut): void {
            $record = $employee->attendanceRecords()->where('attendance_date', $date)->latest('id')->first();
            $values = [
                'time_in' => $amIn,
                'am_out' => $amOut,
                'pm_in' => $pmIn,
                'time_out' => $pmOut,
                'total_hours' => round(
                    $amIn->diffInMinutes($amOut) / 60 +
                    $pmIn->diffInMinutes($pmOut) / 60,
                    2,
                ),
                'status' => 'present',
                'source' => 'manual-review',
            ];

            if ($record) {
                $record->update($values);
            } else {
                $employee->attendanceRecords()->create($values + ['attendance_date' => $date]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance day confirmed.']);

        return back();
    }
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
        $punches = AttendancePunch::query()->ownedBy($employee->id);
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
        $records = $attendance
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->latest('id')
            ->get(['attendance_date', 'time_in', 'am_out', 'pm_in', 'time_out', 'status'])
            ->unique('attendance_date')
            ->keyBy(fn (AttendanceRecord $record) => (int) substr($record->attendance_date, -2))
            ->map(fn (AttendanceRecord $record) => [
                'amIn' => $record->time_in ? Date::parse($record->time_in)->format('H:i') : null,
                'amOut' => $record->am_out ? Date::parse($record->am_out)->format('H:i') : null,
                'pmIn' => $record->pm_in ? Date::parse($record->pm_in)->format('H:i') : null,
                'pmOut' => $record->time_out ? Date::parse($record->time_out)->format('H:i') : null,
                'remarks' => $record->status === 'present' ? null : ucfirst(str_replace('_', ' ', $record->status)),
            ])->all();

        $punchTimes = $punches->whereBetween('punched_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderBy('punched_at')->pluck('punched_at')
            ->groupBy(fn (string $time) => (int) substr($time, 8, 2))
            ->map(fn ($times) => $times->map(fn (string $time) => substr($time, 11))->all());
        foreach ($punchTimes as $day => $times) {
            if (isset($records[$day])) {
                continue;
            }
            $count = count($times);
            // Four ordered punches fill the four slots. Other counts need HR review.
            $records[$day] = $count === AttendancePunch::DTR_PUNCH_COUNT
                ? array_combine(['amIn', 'amOut', 'pmIn', 'pmOut'], array_map(fn (string $time) => substr($time, 0, 5), $times))
                : ['remarks' => "Review {$count} punch(es)"];
        }

        $givenNames = implode(' ', array_filter([$employee->first_name, $employee->middle_name, $employee->suffix]));

        $preview = [
            'name' => "{$employee->last_name}, {$givenNames}",
            'month' => $calendar->format('Y-m'),
            'period' => $period,
            'periodLabel' => $periodLabel,
            'records' => (object) $records,
            'punchTimes' => (object) $punchTimes->all(),
            'canReview' => Gate::allows('manage-hris-resource', 'attendance'),
            'reviewUrl' => route('hris.attendance.dtr.review', $employee, false),
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
