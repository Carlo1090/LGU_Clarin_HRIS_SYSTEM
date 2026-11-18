<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHrisAccountReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user->is_active, 403);

        $passwordSetupRoute = $request->routeIs('security.edit', 'user-password.update');
        if ($user->must_change_password && ! $passwordSetupRoute) {
            return to_route('security.edit');
        }

        if ($user->requires_mfa && ! $user->hasEnabledTwoFactorAuthentication() && ! $passwordSetupRoute) {
            return to_route('security.edit');
        }

        return $next($request);
    }
}
