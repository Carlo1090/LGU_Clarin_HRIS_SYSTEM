<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Support\HrisEnrollmentPolicy;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHrisAccountReady
{
    public function handle(Request $request, Closure $next, ?string $emailPolicy = null): Response
    {
        $user = $request->user();
        abort_unless($user->is_active, 403);

        $onboarded = $user->role?->name === 'Employee'
            && AuditLog::query()->where('user_id', $user->id)->where('action', 'employee.activation_completed')->exists();
        if ($onboarded && ! $request->routeIs('hris.my-profile', 'hris.my-profile.draft', 'hris.my-profile.submit', 'hris.employee.identifier.reveal')
            && ! AuditLog::query()->where('action', 'employee.profile_approved')->where('model_type', Employee::class)
                ->where('model_id', $user->employee_id)->exists()) {
            return to_route('hris.my-profile');
        }

        if ($emailPolicy === 'verify-legacy' && ! $onboarded && $user->role?->name !== 'Employee'
            && HrisEnrollmentPolicy::offers($user, 'email_verification') && ! $user->hasVerifiedEmail()) {
            return app(EnsureEmailIsVerified::class)->handle($request, $next);
        }

        $passwordSetupRoute = $request->routeIs('security.edit', 'user-password.update');
        if ($user->must_change_password && ! $passwordSetupRoute) {
            return to_route('security.edit');
        }

        if ($user->requires_mfa && HrisEnrollmentPolicy::offers($user, 'totp')
            && ! $user->hasEnabledTwoFactorAuthentication() && ! $passwordSetupRoute) {
            return to_route('security.edit');
        }

        return $next($request);
    }
}
