<?php

namespace App\Http\Middleware;

use App\Support\HrisEnrollmentPolicy;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExpireIdleHrisSession
{
    public const MINUTES = ['HRMO Head' => 15, 'HR Officer' => 15, 'Employee' => 30];

    public const LAST_ACTIVITY = 'hris.last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        $user = $guard->user();
        $minutes = self::MINUTES[$user?->role?->name ?? ''] ?? null;
        if ($minutes === null || ! $guard instanceof SessionGuard) {
            return $next($request);
        }

        $activity = $request->session()->get(self::LAST_ACTIVITY);
        $now = now()->timestamp;
        if ($guard->viaRemember() || (is_int($activity) && $now - $activity >= $minutes * 60)) {
            $guard->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Your session expired. Sign in again.'], 401)
                : to_route('login')->with('status', 'Your session expired. Sign in again.');
        }

        $request->session()->put(self::LAST_ACTIVITY, $now);
        if ($request->routeIs('two-factor.enable', 'two-factor.confirm', 'two-factor.qr-code', 'two-factor.secret-key')) {
            abort_unless(HrisEnrollmentPolicy::offers($user, 'totp'), 403);
        }
        if ($request->routeIs('passkey.registration-options', 'passkey.store')) {
            abort_unless(HrisEnrollmentPolicy::offers($user, 'passkeys'), 403);
        }
        if ($request->routeIs('verification.notice', 'verification.send')) {
            abort_unless($user->email !== null && HrisEnrollmentPolicy::offers($user, 'email_verification'), 403);
        }

        return $next($request);
    }
}
