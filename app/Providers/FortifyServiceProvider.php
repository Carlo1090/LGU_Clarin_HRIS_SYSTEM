<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Middleware\ExpireIdleHrisSession;
use App\Models\AuditLog;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passkeys::authorizeLoginUsing(function (Request $request, User $user): bool {
            if (! isset(ExpireIdleHrisSession::MINUTES[$user->role?->name ?? ''])) {
                return true;
            }
            $request->merge(['remember' => false]);

            return $user->is_active && $user->password !== null;
        });

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if (! $user instanceof User || $event->guard !== 'web' || ! isset(ExpireIdleHrisSession::MINUTES[$user->role?->name ?? ''])
                || $event->remember) {
                return;
            }
            $request = request();
            if ($request->hasSession()) {
                $request->session()->put(ExpireIdleHrisSession::LAST_ACTIVITY, now()->timestamp);
            }
            LoginActivity::query()->create(['user_id' => $user->id, 'login_at' => now(), 'status' => 'success']);
        });

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = $this->loginAccount($request);

            if ($user && isset(ExpireIdleHrisSession::MINUTES[$user->role?->name ?? ''])) {
                $request->merge(['remember' => false]);

                return $this->authenticateHrisAccount($request, $user);
            }

            return $user && Hash::check((string) $request->input('password'), $user->password) ? $user : null;
        });
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function loginAccount(Request $request): ?User
    {
        if (! $request->attributes->has(User::class)) {
            $login = Str::lower(trim((string) $request->input(Fortify::username())));
            $request->attributes->set(User::class, User::query()->with('role')->where('email', $login)
                ->orWhereHas('employee', fn ($query) => $query->whereRaw('LOWER(employee_number) = ?', [$login]))->first());
        }

        return $request->attributes->get(User::class);
    }

    private function authenticateHrisAccount(Request $request, User $user): ?User
    {
        $account = 'hris-login:account:'.$user->id;
        $device = 'hris-login:device:'.hash('sha256', $request->session()->getId().'|'.$request->ip());
        $network = 'hris-login:ip:'.hash('sha256', (string) $request->ip());
        foreach ([$account => 5, $device => 5, $network => 100] as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                return null;
            }
        }
        if (! $user->is_active || ! Hash::check((string) $request->input('password'), $user->password)) {
            foreach ([$account, $device, $network] as $key) {
                RateLimiter::hit($key, 900);
            }
            if (RateLimiter::attempts($account) === 5) {
                AuditLog::query()->create([
                    'action' => 'employee.login_locked', 'model_type' => User::class, 'model_id' => $user->id,
                    'metadata' => json_encode(['expires_at' => now()->addSeconds(RateLimiter::availableIn($account))->toIso8601String()], JSON_THROW_ON_ERROR),
                ]);
            }

            return null;
        }
        RateLimiter::clear($account);
        RateLimiter::clear($device);

        return $user;
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canSignInWithGoogle' => GoogleLoginController::configured(),
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            if (isset(ExpireIdleHrisSession::MINUTES[$this->loginAccount($request)?->role?->name ?? ''])) {
                return Limit::none();
            }
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
