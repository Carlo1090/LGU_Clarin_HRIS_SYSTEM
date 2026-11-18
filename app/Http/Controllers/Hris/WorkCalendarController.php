<?php

namespace App\Http\Controllers\Hris;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceSchedule;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkCalendarController
{
    public function index(): Response
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        return Inertia::render('hris/work-calendar', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get(['id', 'employee_number', 'first_name', 'last_name']),
            'schedules' => AttendanceSchedule::query()->latest('effective_from')->orderBy('scope_key')->get(),
            'holidays' => AttendanceHoliday::query()->latest('date')->get(['id', 'date', 'name']),
        ]);
    }

    public function saveSchedule(Request $request): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');
        $input = $request->validate([
            'scope' => ['required', 'in:municipal,office,employee'],
            'department_id' => ['exclude_unless:scope,office', 'required', 'integer', Rule::exists('departments', 'id')],
            'employee_id' => ['exclude_unless:scope,employee', 'required', 'integer', Rule::exists('employees', 'id')],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'workdays' => ['required', 'array', 'min:1', 'max:7'],
            'workdays.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'am_in' => ['required', 'date_format:H:i'],
            'am_out' => ['required', 'date_format:H:i', 'after:am_in'],
            'pm_in' => ['required', 'date_format:H:i', 'after:am_out'],
            'pm_out' => ['required', 'date_format:H:i', 'after:pm_in'],
        ]);
        $scopeKey = match ($input['scope']) {
            'office' => 'office:'.$input['department_id'],
            'employee' => 'employee:'.$input['employee_id'],
            default => 'municipal',
        };
        unset($input['scope']);
        $input['workdays'] = array_map('intval', array_values($input['workdays']));
        sort($input['workdays']);

        DB::transaction(function () use ($request, $input, $scopeKey): void {
            $schedule = AttendanceSchedule::query()->updateOrCreate(
                ['scope_key' => $scopeKey, 'effective_from' => $input['effective_from']],
                $input,
            );
            $this->audit($request, 'attendance.schedule_saved', $schedule);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Work schedule saved.']);

        return back();
    }

    public function saveHoliday(Request $request): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');
        $input = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'name' => ['required', 'string', 'max:255']]);

        DB::transaction(function () use ($request, $input): void {
            $holiday = AttendanceHoliday::query()->updateOrCreate(['date' => $input['date']], ['name' => $input['name']]);
            $this->audit($request, 'attendance.holiday_saved', $holiday);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Holiday saved.']);

        return back();
    }

    public function deleteSchedule(Request $request, AttendanceSchedule $schedule): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');
        DB::transaction(function () use ($request, $schedule): void {
            $this->audit($request, 'attendance.schedule_removed', $schedule);
            $schedule->delete();
        });

        return back();
    }

    public function deleteHoliday(Request $request, AttendanceHoliday $holiday): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');
        DB::transaction(function () use ($request, $holiday): void {
            $this->audit($request, 'attendance.holiday_removed', $holiday);
            $holiday->delete();
        });

        return back();
    }

    private function audit(Request $request, string $action, AttendanceSchedule|AttendanceHoliday $entry): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'model_type' => $entry::class,
            'model_id' => $entry->id,
            'metadata' => json_encode($entry->getAttributes(), JSON_THROW_ON_ERROR),
        ]);
    }
}
