<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hris\AttendanceCorrectionController;
use App\Http\Controllers\Hris\HrisResourceController;
use App\Http\Controllers\Hris\LeaveReviewController;
use App\Http\Controllers\Hris\PortalController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('hris')->name('hris.')->group(function () {
        Route::get('portal/{page}', PortalController::class)
            ->whereIn('page', ['my-profile', 'my-attendance', 'my-leave-credits', 'my-leave-requests', 'my-payslips', 'staffing', 'plantilla-summary'])
            ->name('portal');
        Route::post('portal/my-leave-requests', [PortalController::class, 'submitLeave'])
            ->name('portal.leave.store');
        Route::post('leave-applications/{leave}/review', LeaveReviewController::class)->name('leave.review');
        Route::get('attendance-corrections', [AttendanceCorrectionController::class, 'index'])->name('attendance-corrections.index');
        Route::post('attendance-corrections', [AttendanceCorrectionController::class, 'store'])->name('attendance-corrections.store');
        Route::post('attendance-corrections/{correction}/review', [AttendanceCorrectionController::class, 'review'])->name('attendance-corrections.review');
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
