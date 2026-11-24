<?php

namespace App\Attendance\Support;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Models\Department;
use App\Models\Employee;

class HrisEmployeeDirectory implements EmployeeDirectory
{
    public function list(?array $machineIds = null, ?int $departmentId = null, bool $forUpdate = false): array
    {
        return Employee::query()
            ->when($machineIds !== null, fn ($query) => $query->whereIntegerInRaw('biometric_id', $machineIds))
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when($forUpdate, fn ($query) => $query->orderBy('id')->lockForUpdate())
            ->get(['id', 'biometric_id'])
            ->map(fn (Employee $employee): array => [
                'id' => (int) $employee->id,
                'biometric_id' => $employee->biometric_id === null ? null : (int) $employee->biometric_id,
            ])->all();
    }

    public function departmentExists(int $departmentId): bool
    {
        return Department::query()->whereKey($departmentId)->exists();
    }

    public function find(int $employeeId, bool $forUpdate = false): ?array
    {
        $employee = Employee::query()
            ->when($forUpdate, fn ($query) => $query->lockForUpdate())
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
