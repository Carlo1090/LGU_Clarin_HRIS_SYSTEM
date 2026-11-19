<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Features;

class GoogleLoginController
{
    public static function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(self::configured(), 404);
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('google_login', [
            'state' => $state,
            'verifier' => $verifier,
            'expires' => now()->addMinutes(10)->timestamp,
        ]);
        $request->session()->forget(['login.id', 'login.remember']);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email',
            'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::configured(), 404);
        $login = $request->session()->pull('google_login');
        $state = $request->query('state');
        $code = $request->query('code');

        if (! $login || $login['expires'] < now()->timestamp || ! is_string($state)
            || ! hash_equals($login['state'], $state) || ! is_string($code) || $code === '') {
            return $this->failed();
        }

        try {
            $token = Http::asForm()->acceptJson()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'code_verifier' => $login['verifier'],
            ]);
            $accessToken = $token->json('access_token');
            if (! $token->successful() || ! is_string($accessToken) || $accessToken === '') {
                return $this->failed();
            }

            $profile = Http::acceptJson()->withToken($accessToken)->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (ConnectionException) {
            return $this->failed();
        }

        $email = $profile->json('email');
        $hostedDomain = $profile->json('hd');
        // Google is authoritative for Gmail and managed Google Workspace addresses.
        if (! $profile->successful() || $profile->json('email_verified') !== true || ! is_string($email)
            || (! str_ends_with(Str::lower($email), '@gmail.com') && (! is_string($hostedDomain) || $hostedDomain === ''))) {
            return $this->failed();
        }

        $user = User::query()->where('email', Str::lower($email))
            ->whereNotNull('email_verified_at')->where('is_active', true)->first();
        if (! $user || $user->must_change_password) {
            return $this->failed();
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            if (! Features::enabled(Features::twoFactorAuthentication())) {
                return $this->failed();
            }

            $request->session()->regenerate();
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);
            TwoFactorAuthenticationChallenged::dispatch($user);

            return to_route('two-factor.login');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function failed(): RedirectResponse
    {
        return to_route('login')->withErrors([
            'google' => 'Google sign-in could not be completed. Use the Google address verified on your HRIS account, or sign in with your password.',
        ]);
    }
}
