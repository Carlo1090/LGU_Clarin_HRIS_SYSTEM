<?php

namespace App\Http\Controllers\Hris;

use App\Http\Requests\Hris\UpdateBiometricLinkRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BiometricLinkController
{
    public function __invoke(UpdateBiometricLinkRequest $request, Employee $employee): RedirectResponse
    {
        $input = $request->validated('biometric_id');
        $id = $input === null ? null : (int) $input;

        try {
            DB::transaction(function () use ($request, $employee, $id): void {
                $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
                $old = $employee->biometric_id;
                if ($old === $id) {
                    return;
                }

                $employee->biometric_id = $id;
                $employee->biometric_status = 'pending';
                $employee->biometric_note = null;
                $employee->biometric_verified_at = null;
                $employee->biometric_verified_by = null;
                $employee->save();

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'employee.biometric_link_updated',
                    'model_type' => Employee::class,
                    'model_id' => $employee->id,
                    'metadata' => json_encode(['old' => $old, 'new' => $id], JSON_THROW_ON_ERROR),
                ]);
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $owner = Employee::query()->where('biometric_id', $id)->value('employee_number');
            if ($owner === null) {
                throw $exception;
            }

            throw ValidationException::withMessages(['biometric_id' => "Biometric ID {$id} is already linked to {$owner}."]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $id === null ? 'Biometric ID removed.' : 'Biometric ID saved.']);

        return back();
    }
}
