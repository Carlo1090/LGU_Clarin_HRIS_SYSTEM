<?php

namespace App\Attendance\Controllers;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Services\AttendanceRecomputer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class EmployeeDayController
{
    public function __invoke(Request $request, int $employee, string $date, AttendanceRecomputer $attendance, EmployeeDirectory $employees)
    {
        Gate::authorize('view-hris-resource', 'attendance');
        if ($request->user()->role?->name === 'Department Head') {
            $departmentId = $request->user()->employee?->department_id;
            abort_unless($departmentId !== null && ($employees->find($employee)['department_id'] ?? null) === $departmentId, 403);
        }
        Validator::validate(['date' => $date], ['date' => 'required|date_format:Y-m-d']);

        return response()->json(['employee_id' => $employee, 'work_date' => $date] + $attendance->employeeDay($employee, $date));
    }
}
