<?php

namespace App\Attendance\Contracts;

interface EmployeeDirectory
{
    /**
     * @return array{id: int, department_id: int|null, biometric_id: int|null}|null
     */
    public function find(int $employeeId, bool $forUpdate = false): ?array;

    /** @return list<array{id: int, biometric_id: int|null}> */
    public function list(?array $machineIds = null, ?int $departmentId = null, bool $forUpdate = false): array;

    public function departmentExists(int $departmentId): bool;

    /**
     * @return array{id: int, name: string}|null
     */
    public function departmentOf(int $employeeId): ?array;
}
