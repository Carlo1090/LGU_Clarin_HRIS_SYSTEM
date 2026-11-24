<?php

namespace App\Providers;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Services\PunchClassifier;
use App\Attendance\Support\HrisEmployeeDirectory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmployeeDirectory::class, HrisEmployeeDirectory::class);
        $this->app->bind(PunchClassifier::class, fn () => new PunchClassifier(
            duplicateWindowSeconds: config('attendance.duplicate_window_seconds'),
            amInHoursBefore: config('attendance.classifier.am_in_hours_before'),
            pmOutHoursAfter: config('attendance.classifier.pm_out_hours_after'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $access = [
            'Employee' => [],
            'Department Head' => ['employees', 'attendance', 'leave-applications', 'performance-reviews'],
            'HR Officer' => ['departments', 'positions', 'plantilla-items', 'employees', 'attendance', 'leave-types', 'leave-applications', 'vacancies', 'applicants', 'evaluation-periods', 'performance-reviews', 'announcements'],
            'HRMO Head' => ['departments', 'positions', 'plantilla-items', 'employees', 'attendance', 'leave-types', 'leave-applications', 'vacancies', 'applicants', 'evaluation-periods', 'performance-reviews', 'announcements'],
            'Payroll Officer' => ['payroll-periods', 'payrolls'],
            'Accountant' => ['payrolls'],
            'Treasurer' => ['payrolls'],
            'Mayor' => ['departments', 'vacancies'],
            'MIS Administrator' => ['roles', 'system-settings'],
            'DPO' => ['privacy-requests', 'privacy-incidents'],
            'Auditor' => ['payrolls'],
            'Administrator' => ['*'],
        ];
        $manage = [
            'HR Officer' => $access['HR Officer'],
            'HRMO Head' => $access['HRMO Head'],
            'Payroll Officer' => $access['Payroll Officer'],
            'MIS Administrator' => $access['MIS Administrator'],
            'DPO' => $access['DPO'],
            'Administrator' => ['*'],
        ];

        Gate::define('view-hris-resource', fn (User $user, string $resource): bool => in_array($resource, $access[$user->role?->name] ?? [], true) || in_array('*', $access[$user->role?->name] ?? [], true));
        Gate::define('manage-hris-resource', fn (User $user, string $resource): bool => ! in_array($resource, ['roles', 'departments'], true)
            && (in_array($resource, $manage[$user->role?->name] ?? [], true) || in_array('*', $manage[$user->role?->name] ?? [], true)));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
