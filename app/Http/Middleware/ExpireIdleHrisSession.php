<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
