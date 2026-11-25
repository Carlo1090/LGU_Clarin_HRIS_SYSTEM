<?php

namespace App\Http\Controllers\Hris;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeProfileRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeIdentifierRevealController
{
    public function __invoke(Request $request, Employee $employee, string $field): JsonResponse
    {
        $actor = $request->user();
        $role = $actor->role?->name;
        abort_unless($role === 'HRMO Head' || ($role === 'Employee' && (int) $actor->employee_id === $employee->id), 403);
        $request->validate(['password' => ['required', 'string', 'current_password']]);

        return $this->reveal($actor->id, $employee->id, $field, $employee->getAttribute($field));
    }

    public function submitted(Request $request, EmployeeProfileRevision $revision): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->role?->name === 'HRMO Head' && in_array($revision->field, ['tin', 'gsis_bp_number'], true), 403);
        $request->validate(['password' => ['required', 'string', 'current_password']]);
        $value = $revision->submitted['not_applicable'] ? null : $revision->submitted['value'];

        return $this->reveal($actor->id, $revision->employee_id, $revision->field, $value, $revision->id);
    }

    private function reveal(int $actorId, int $employeeId, string $field, ?string $value, ?int $revisionId = null): JsonResponse
    {
        AuditLog::query()->create([
            'user_id' => $actorId,
            'action' => 'employee.identifier_revealed',
            'model_type' => Employee::class,
            'model_id' => $employeeId,
            'metadata' => json_encode(['field' => $field, 'revision_id' => $revisionId], JSON_THROW_ON_ERROR),
        ]);

        return response()->json(['value' => $value], headers: ['Cache-Control' => 'private, no-store']);
    }
}
