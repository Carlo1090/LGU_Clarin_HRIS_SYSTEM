<?php

namespace App\Http\Controllers\Hris;

use App\Attendance\Models\BiometricLink;
use App\Attendance\Services\AttendanceRecomputer;
use App\Http\Requests\Hris\UpdateBiometricLinkRequest;
use App\Models\AttendancePunch;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\BiometricLinkVerifier;
use App\Support\HrisPasswordConfirmation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BiometricLinkController
{
    public function __invoke(UpdateBiometricLinkRequest $request, Employee $employee, BiometricLinkVerifier $verifier, AttendanceRecomputer $attendance): RedirectResponse
    {
        $input = $request->validated('biometric_id');
        $id = $input === null ? null : (int) $input;

        try {
            $changed = DB::transaction(function () use ($request, $employee, $id, $verifier, $attendance): bool {
                $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
                $old = $employee->biometric_id;

                if ($old !== $id) {
                    if ($request->validated('verify_only')) {
                        throw ValidationException::withMessages(['biometric_id' => 'The saved biometric ID changed. Refresh the employee list and try again.']);
                    }
                    if ($employee->biometric_locked) {
                        throw ValidationException::withMessages(['biometric_id' => 'This ID is locked. Select Unlock before changing or removing it.']);
                    }

                    $from = $this->effectiveDate($request, 'effective_from');
                    $to = $this->effectiveDate($request, 'effective_to');
                    if ($old !== null) {
                        $hasPunches = AttendancePunch::query()->where('biometric_id', $old)->exists();
                        abort_if($hasPunches && $request->user()->role?->name !== 'HRMO Head', 403);
                        HrisPasswordConfirmation::require($request);
                        $previous = $employee->biometricLinks()->where('biometric_id', $old)->latest('id')->lockForUpdate()->first();
                        if ($previous?->effective_to === null) {
                            $cutoff = $this->effectiveDate($request, 'previous_effective_to') ?? $from;
                            if ($cutoff === null || ($previous?->effective_from !== null && $cutoff <= $previous->effective_from)) {
                                throw ValidationException::withMessages(['biometric_id' => 'Enter when the previous link ends to retain its punch history.']);
                            }
                            if ($previous === null) {
                                $employee->biometricLinks()->create([
                                    'biometric_id' => $old, 'effective_to' => $cutoff, 'recorded_by' => $request->user()->id,
                                ]);
                            } else {
                                $previous->update(['effective_to' => $cutoff]);
                            }
                        }
                    }

                    if ($id !== null) {
                        if ($request->user()->role?->name !== 'HRMO Head'
                            && BiometricLink::query()->where('biometric_id', $id)->where('employee_id', '!=', $employee->id)->exists()
                            && AttendancePunch::query()->where('biometric_id', $id)->exists()) {
                            abort(403);
                        }
                        $overlaps = BiometricLink::query()
                            ->where(fn ($query) => $query->where('biometric_id', $id)->orWhere('employee_id', $employee->id))
                            ->when($from !== null, fn ($query) => $query->where(fn ($end) => $end->whereNull('effective_to')->orWhere('effective_to', '>', $from)))
                            ->when($to !== null, fn ($query) => $query->where(fn ($start) => $start->whereNull('effective_from')->orWhere('effective_from', '<', $to)))
                            ->lockForUpdate()->first();
                        if ($overlaps !== null) {
                            throw ValidationException::withMessages(['biometric_id' => 'These dates overlap an existing Bio ID link.']);
                        }
                        $employee->biometricLinks()->create([
                            'biometric_id' => $id, 'effective_from' => $from, 'effective_to' => $to, 'recorded_by' => $request->user()->id,
                        ]);
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
                        'metadata' => json_encode([
                            'old' => $old, 'new' => $id, 'effective_from' => $from?->toDateTimeString(), 'effective_to' => $to?->toDateTimeString(),
                        ], JSON_THROW_ON_ERROR),
                    ]);
                }

                if ($id !== null) {
                    $verifier->verifyPending($request->user(), $employee->id);
                }
                if ($old !== $id) {
                    $attendance->recompute($employee->id);
                }

                return $old !== $id;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $owner = Employee::query()->where('biometric_id', $id)->value('employee_number');
            if ($owner === null) {
                throw $exception;
            }

            throw ValidationException::withMessages(['biometric_id' => "Biometric ID {$id} is already linked to {$owner}."]);
        }

        $message = match (true) {
            $id === null => 'Biometric ID removed.',
            $changed => 'Biometric ID saved.',
            default => 'Biometric ID checked.',
        };
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function effectiveDate(UpdateBiometricLinkRequest $request, string $field): ?Carbon
    {
        $value = $request->validated($field);

        return $value === null ? null : Carbon::parse($value, config('attendance.timezone'));
    }

    public function setLock(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless(in_array($request->user()->role?->name, ['HR Officer', 'HRMO Head'], true), 403);
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

        Inertia::flash('toast', ['type' => 'success', 'message' => $locked ? 'Biometric ID locked.' : 'Biometric ID unlocked. You can edit it now.']);

        return back();
    }
}
