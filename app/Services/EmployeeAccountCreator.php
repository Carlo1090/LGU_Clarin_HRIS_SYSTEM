<?php

namespace App\Services;

use App\Attendance\Models\BiometricLink;
use App\Attendance\Services\AttendanceRecomputer;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type ActivationSlip array{employee_number: string, name: string, department: string, activation_code: string, expires_at: string, issuance_id: int}
 */
class EmployeeAccountCreator
{
    public const DEFAULT_EXPIRY_DAYS = 14;

    public function __construct(private AttendanceRecomputer $attendance, private BiometricLinkVerifier $verifier) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return ActivationSlip
     */
    public function create(array $attributes, ?string $email, User $actor, int $expiryDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        return DB::transaction(function () use ($attributes, $email, $actor, $expiryDays): array {
            $department = Department::query()->find($attributes['department_id'] ?? null);
            if ($department === null) {
                throw ValidationException::withMessages(['department_id' => 'Select an existing department.']);
            }

            $bioId = $attributes['biometric_id'] ?? null;
            unset($attributes['biometric_id']);
            abort_if($bioId !== null && ! in_array($actor->role?->name, ['HR Officer', 'HRMO Head'], true), 403);
            if ($bioId !== null && (
                Employee::query()->where('biometric_id', $bioId)->exists()
                || BiometricLink::query()->where('biometric_id', $bioId)->exists()
            )) {
                throw ValidationException::withMessages(['biometric_id' => 'This Bio ID has an existing link. Assign its effective dates in the Bio ID workflow.']);
            }

            $employee = Employee::query()->create($attributes)->setRelation('department', $department);
            $role = Role::query()->where('name', 'Employee')->where('guard_name', 'web')->firstOrFail();
            $name = trim($employee->first_name.' '.$employee->last_name);
            $account = new User(['name' => $name, 'email' => $email, 'password' => null]);
            $account->forceFill([
                'username' => $employee->employee_number,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'requires_mfa' => true,
            ])->save();
            $employee->forceFill(['user_id' => $account->id, 'biometric_id' => $bioId])->save();

            if ($bioId !== null) {
                $link = $employee->biometricLinks()->create(['biometric_id' => $bioId, 'recorded_by' => $actor->id]);
                AuditLog::query()->create([
                    'user_id' => $actor->id, 'action' => 'employee.biometric_link_updated',
                    'model_type' => BiometricLink::class, 'model_id' => $link->id,
                ]);
                $this->verifier->verifyPending($actor, $employee->id);
                $this->attendance->recompute($employee->id);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id, 'action' => 'employee.account_created',
                'model_type' => Employee::class, 'model_id' => $employee->id,
            ]);

            return $this->issue($employee, $account, $actor, $expiryDays);
        });
    }

    /** @return ActivationSlip */
    public function reissue(Employee $employee, User $actor): array
    {
        return DB::transaction(function () use ($employee, $actor): array {
            $account = $employee->user()->lockForUpdate()->firstOrFail();
            abort_unless($account->role?->name === 'Employee' && $account->is_active, 422, 'Only enabled Employee accounts can receive an activation code.');
            $account->activationCodes()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);

            return $this->issue($employee, $account, $actor, self::DEFAULT_EXPIRY_DAYS, reissued: true);
        });
    }

    /** @return ActivationSlip */
    private function issue(Employee $employee, User $account, User $actor, int $expiryDays, bool $reissued = false): array
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($index = 0; $index < 8; $index++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $expiresAt = now()->addDays($expiryDays);
        $activation = $account->activationCodes()->create([
            'issued_by' => $actor->id,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);
        AuditLog::query()->create([
            'user_id' => $actor->id, 'action' => $reissued ? 'employee.activation_code_reissued' : 'employee.activation_code_issued',
            'model_type' => $activation::class, 'model_id' => $activation->id,
        ]);

        return [
            'employee_number' => $employee->employee_number,
            'name' => $account->name,
            'department' => $employee->department->name ?? '',
            'activation_code' => substr($code, 0, 4).'-'.substr($code, 4),
            'expires_at' => $expiresAt->copy()->timezone(config('attendance.timezone'))->format('Y-m-d H:i P'),
            'issuance_id' => $activation->id,
        ];
    }
}
