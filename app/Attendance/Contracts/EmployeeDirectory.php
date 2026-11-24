<?php

namespace App\Attendance\Contracts;

interface EmployeeDirectory
{
    /**
     * @return array{id: int, department_id: int|null, biometric_id: int|null}|null
     */
    public function find(int $employeeId): ?array;

    /**
     * @return array{id: int, name: string}|null
     */
    public function departmentOf(int $employeeId): ?array;
}
