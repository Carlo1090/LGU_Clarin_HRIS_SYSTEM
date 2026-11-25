<?php

namespace App\Http\Controllers\Hris;

use App\Models\AuditLog;
use App\Models\EmployeeActivationCode;
use App\Models\User;
use App\Services\EmployeeAccountDeactivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeActivationController
{
    public function create(): Response
    {
        return Inertia::render('auth/employee-activation');
    }

    public function store(Request $request, EmployeeAccountDeactivation $accounts): RedirectResponse
    {
        $data = $request->validate([
            'employee_number' => ['required', 'string', 'max:255'],
            'activation_code' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ]);
        $number = Str::lower(trim($data['employee_number']));
        $code = strtoupper(preg_replace('/[\s-]+/', '', $data['activation_code']) ?? '');
        $keys = [
            'activation:employee:'.hash('sha256', $number) => 5,
            'activation:device:'.hash('sha256', $request->session()->getId().'|'.$request->ip()) => 5,
            'activation:ip:'.hash('sha256', (string) $request->ip()) => 100,
        ];
        foreach ($keys as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $this->invalidCode();
            }
        }

        $account = DB::transaction(function () use ($number, $code, $data, $accounts): ?User {
            $user = User::query()->where('is_active', true)->whereHas('role', fn ($role) => $role->where('name', 'Employee'))
                ->whereHas('employee', fn ($employee) => $employee->whereRaw('LOWER(employee_number) = ?', [$number]))
                ->lockForUpdate()->first();
            $issuance = $user?->activationCodes()->whereNull('revoked_at')->whereNull('used_at')->latest('id')->first();
            if ($issuance === null || $issuance->expires_at->isPast() || ! Hash::check($code, $issuance->code_hash)) {
                $this->failedAttempt($issuance);

                return null;
            }
            $password = $data['password'];
            $normalized = Str::lower(trim($password));
            if (strlen($password) > 72 || in_array($normalized, [$number, Str::lower(trim($user->name))], true)
                || in_array($normalized, config('employee_activation.common_passwords'), true)) {
                throw ValidationException::withMessages(['password' => 'Choose a different passphrase of at least 12 characters and at most 72 bytes.']);
            }

            $recovery = $user->password !== null || AuditLog::query()->where('action', 'employee.account_reactivation_started')
                ->where('model_type', User::class)->where('model_id', $user->id)->exists();
            $revoked = $accounts->revokeSessions($user);
            $user->forceFill(['password' => $password, 'must_change_password' => false, 'requires_mfa' => false])->save();
            $issuance->forceFill(['used_at' => now()])->save();
            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => $recovery ? 'employee.activation_recovery_used' : 'employee.activation_completed',
                'model_type' => EmployeeActivationCode::class, 'model_id' => $issuance->id,
                'metadata' => json_encode(['sessions_revoked' => $revoked], JSON_THROW_ON_ERROR),
            ]);

            return $user;
        }, attempts: 3);

        if ($account === null) {
            foreach (array_keys($keys) as $key) {
                RateLimiter::hit($key, 900);
            }
            if (RateLimiter::attempts(array_key_first($keys)) === 5) {
                $locked = User::query()->whereHas('role', fn ($role) => $role->where('name', 'Employee'))
                    ->whereHas('employee', fn ($employee) => $employee->whereRaw('LOWER(employee_number) = ?', [$number]))->value('id');
                if ($locked !== null) {
                    AuditLog::query()->create([
                        'action' => 'employee.activation_locked', 'model_type' => User::class, 'model_id' => $locked,
                        'metadata' => json_encode(['expires_at' => now()->addSeconds(RateLimiter::availableIn(array_key_first($keys)))->toIso8601String()], JSON_THROW_ON_ERROR),
                    ]);
                }
            }
            $this->invalidCode();
        }

        RateLimiter::clear(array_key_first($keys));
        Auth::login($account);
        $request->session()->regenerate();

        return to_route('hris.my-profile');
    }

    private function failedAttempt(?EmployeeActivationCode $issuance): void
    {
        $failures = AuditLog::query()->where('action', 'employee.activation_failed')
            ->where('model_type', EmployeeActivationCode::class);
        AuditLog::query()->create([
            'action' => 'employee.activation_failed', 'model_type' => EmployeeActivationCode::class,
            'model_id' => $issuance?->id,
        ]);
        if ($issuance !== null && $failures->where('model_id', $issuance->id)->count() >= 25) {
            $issuance->forceFill(['revoked_at' => now()])->save();
        }
    }

    private function invalidCode(): never
    {
        throw ValidationException::withMessages(['activation_code' => 'The employee number or activation code could not be verified.']);
    }
}
