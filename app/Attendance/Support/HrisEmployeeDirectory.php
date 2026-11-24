<?php

namespace App\Attendance\Support;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Models\Employee;

class HrisEmployeeDirectory implements EmployeeDirectory
{
    public function find(int $employeeId): ?array
    {
        $employee = Employee::query()
            ->select(['id', 'department_id', 'biometric_id'])
            ->find($employeeId);

        if (! $employee) {
            return null;
        }

        return [
            'id' => (int) $employee->id,
            'department_id' => $employee->department_id === null ? null : (int) $employee->department_id,
            'biometric_id' => $employee->biometric_id === null ? null : (int) $employee->biometric_id,
        ];
    }

    public function departmentOf(int $employeeId): ?array
    {
        $employee = Employee::query()
            ->with('department:id,name')
            ->select(['id', 'department_id'])
            ->find($employeeId);

        if (! $employee || ! $employee->department) {
            return null;
        }

        return [
            'id' => (int) $employee->department->id,
            'name' => (string) $employee->department->name,
        ];
    }
}
