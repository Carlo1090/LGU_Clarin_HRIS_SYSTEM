<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hris\HrisResourceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('hris')->name('hris.')->group(function () {
        Route::redirect('/', '/hris/departments')->name('index');
        Route::get('{resource}', [HrisResourceController::class, 'index'])
            ->whereIn('resource', ['roles', 'departments', 'positions', 'plantilla-items', 'employees', 'attendance', 'leave-types', 'leave-applications', 'payroll-periods', 'payrolls', 'vacancies', 'applicants', 'evaluation-periods', 'performance-reviews', 'privacy-requests', 'privacy-incidents', 'announcements', 'system-settings'])
            ->name('resources.index');
        Route::post('{resource}', [HrisResourceController::class, 'store'])
            ->whereIn('resource', ['roles', 'departments', 'positions', 'plantilla-items', 'employees', 'attendance', 'leave-types', 'leave-applications', 'payroll-periods', 'payrolls', 'vacancies', 'applicants', 'evaluation-periods', 'performance-reviews', 'privacy-requests', 'privacy-incidents', 'announcements', 'system-settings'])
            ->name('resources.store');
        Route::put('{resource}/{record}', [HrisResourceController::class, 'update'])
            ->whereIn('resource', ['roles', 'departments', 'positions', 'plantilla-items', 'employees', 'attendance', 'leave-types', 'leave-applications', 'payroll-periods', 'payrolls', 'vacancies', 'applicants', 'evaluation-periods', 'performance-reviews', 'privacy-requests', 'privacy-incidents', 'announcements', 'system-settings'])
            ->whereNumber('record')
            ->name('resources.update');
        Route::delete('{resource}/{record}', [HrisResourceController::class, 'destroy'])
            ->whereIn('resource', ['roles', 'departments', 'positions', 'plantilla-items', 'employees', 'attendance', 'leave-types', 'leave-applications', 'payroll-periods', 'payrolls', 'vacancies', 'applicants', 'evaluation-periods', 'performance-reviews', 'privacy-requests', 'privacy-incidents', 'announcements', 'system-settings'])
            ->whereNumber('record')
            ->name('resources.destroy');
    });
});

require __DIR__.'/settings.php';
