<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class EmployeeAccountDeactivation
{
    public function __construct(private EmployeeAccountCreator $accounts) {}

    public function grantFallback(User $head, User $administrator, #[\SensitiveParameter] string $password): void
    {
        DB::transaction(function () use ($head, $administrator, $password): void {
            $head = User::query()->lockForUpdate()->findOrFail($head->id);
            abort_unless($head->is_active && $head->role?->name === 'HRMO Head', 403);
            $this->confirmPassword($head, $password);
            $administrator = User::query()->lockForUpdate()->findOrFail($administrator->id);
            abort_unless($administrator->is_active && $administrator->role?->name === 'MIS Administrator', 403);

            DB::table('employee_deactivation_permissions')->updateOrInsert(
                ['user_id' => $administrator->id],
                ['granted_by' => $head->id, 'granted_at' => now()],
            );
            $this->audit($head, $administrator, 'employee.deactivation_fallback_granted', []);
        }, attempts: 3);
    }

    public function revokeFallback(User $head, User $administrator, #[\SensitiveParameter] string $password): void
    {
        DB::transaction(function () use ($head, $administrator, $password): void {
            $head = User::query()->lockForUpdate()->findOrFail($head->id);
            abort_unless($head->is_active && $head->role?->name === 'HRMO Head', 403);
            $this->confirmPassword($head, $password);
            if (DB::table('employee_deactivation_permissions')->where('user_id', $administrator->id)->delete()) {
                $this->audit($head, $administrator, 'employee.deactivation_fallback_revoked', []);
            }
        }, attempts: 3);
    }

    public function deactivate(User $actor, User $employee, #[\SensitiveParameter] string $password, string $reason): void
    {
        DB::transaction(function () use ($actor, $employee, $password, $reason): void {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $role = $actor->role?->name;
            $fallback = $role === 'MIS Administrator' && DB::table('employee_deactivation_permissions')
                ->where('user_id', $actor->id)->lockForUpdate()->first() !== null;
            abort_unless($actor->is_active && ($role === 'HRMO Head' || $fallback), 403);
            $this->confirmPassword($actor, $password);
            $reason = Validator::make(['reason' => trim($reason)], ['reason' => ['required', 'string', 'max:1000']])
                ->validate()['reason'];

            $employee = User::query()->lockForUpdate()->findOrFail($employee->id);
            abort_unless($employee->role?->name === 'Employee', 403);
            abort_unless($employee->is_active, 409, 'This employee account is already deactivated.');

            $employee->forceFill(['is_active' => false])->save();
            $employee->activationCodes()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $revoked = $this->revokeSessions($employee);

            $this->audit($actor, $employee, 'employee.account_deactivated', [
                'employee_id' => $employee->employee_id,
                'reason' => $reason,
                'sessions_revoked' => $revoked,
                'fallback' => $fallback,
            ]);
        }, attempts: 3);
    }

    /** @return array{employee_number: string, name: string, department: string, activation_code: string, expires_at: string, issuance_id: int} */
    public function reactivate(User $head, Employee $employee, #[\SensitiveParameter] string $password): array
    {
        return DB::transaction(function () use ($head, $employee, $password): array {
            $head = User::query()->lockForUpdate()->findOrFail($head->id);
            abort_unless($head->is_active && $head->role?->name === 'HRMO Head', 403);
            $this->confirmPassword($head, $password);
            $account = $employee->user()->lockForUpdate()->firstOrFail();
            abort_unless($account->role?->name === 'Employee', 403);
            abort_if($account->is_active, 409, 'This employee account is already enabled.');

            $account->forceFill(['is_active' => true, 'password' => null])->save();
            $revoked = $this->revokeSessions($account);
            $slip = $this->accounts->reissue($employee, $head);
            $this->audit($head, $account, 'employee.account_reactivation_started', [
                'employee_id' => $employee->id,
                'sessions_revoked' => $revoked,
            ]);

            return $slip;
        }, attempts: 3);
    }

    public function revokeSessions(User $account): int
    {
        if (config('session.driver') !== 'database' || DB::connection(config('session.connection')) !== DB::connection()) {
            throw new LogicException('Employee session revocation requires sessions on the application database connection.');
        }

        $account->forceFill(['remember_token' => Str::random(60)])->save();

        return DB::table(config('session.table'))->where('user_id', $account->id)->delete();
    }

    private function confirmPassword(User $actor, #[\SensitiveParameter] string $password): void
    {
        if ($actor->password === null || ! Hash::check($password, $actor->password)) {
            throw ValidationException::withMessages(['password' => 'Confirm your password to continue.']);
        }
    }

    /** @param array<string, mixed> $metadata */
    private function audit(User $actor, User $subject, string $action, array $metadata): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'model_type' => User::class,
            'model_id' => $subject->id,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }
}
