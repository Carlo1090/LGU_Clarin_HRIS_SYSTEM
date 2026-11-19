<?php

namespace App\Http\Controllers\Hris;

use App\Http\Requests\Hris\UpdateBiometricLinkRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

                if ($employee->biometric_locked) {
                    throw ValidationException::withMessages(['biometric_id' => 'Unlock the biometric ID before changing or removing it.']);
                }

                $employee->forceFill(['biometric_id' => $id]);
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

    public function setLock(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');
        $data = $request->validate(['locked' => ['required', 'boolean']]);
        $locked = (bool) $data['locked'];

        DB::transaction(function () use ($request, $employee, $locked): void {
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            if ($employee->biometric_locked === $locked) {
                return;
            }
            if ($locked && $employee->biometric_id === null) {
                throw ValidationException::withMessages(['locked' => 'Save a biometric ID before locking it.']);
            }

            $employee->biometric_locked = $locked;
            $employee->save();

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => $locked ? 'employee.biometric_link_locked' : 'employee.biometric_link_unlocked',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
            ]);
        }, attempts: 3);

        return back();
    }
}
