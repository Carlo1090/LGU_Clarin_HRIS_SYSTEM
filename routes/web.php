<?php

use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hris\AccountInvitationController;
use App\Http\Controllers\Hris\AttendanceCorrectionController;
use App\Http\Controllers\Hris\AttendanceLogImportController;
use App\Http\Controllers\Hris\BiometricLinkController;
use App\Http\Controllers\Hris\DailyPunchController;
use App\Http\Controllers\Hris\DailyTimeRecordController;
use App\Http\Controllers\Hris\EmployeeActivationController;
use App\Http\Controllers\Hris\EmployeeCredentialController;
use App\Http\Controllers\Hris\EmployeeImportController;
use App\Http\Controllers\Hris\EmployeeIdentifierRevealController;
use App\Http\Controllers\Hris\EmployeeProfileDraftController;
use App\Http\Controllers\Hris\EmployeeProfileReviewController;
use App\Http\Controllers\Hris\HrisResourceController;
use App\Http\Controllers\Hris\LeaveApplicationController;
use App\Http\Controllers\Hris\LeaveReviewController;
use App\Http\Controllers\Hris\MonthlyAttendanceReportController;
use App\Http\Controllers\Hris\PortalController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\EnsureHrisAccountReady;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('activate', [EmployeeActivationController::class, 'create'])->name('employee.activate');
    Route::post('activate', [EmployeeActivationController::class, 'store'])->name('employee.activate.store');
});

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('auth/google', [GoogleLoginController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleLoginController::class, 'callback'])->name('google.callback');
});

Route::get('hris/my-profile', [HrisResourceController::class, 'myProfile'])
    ->middleware(['auth', EnsureHrisAccountReady::class.':verify-legacy'])->name('hris.my-profile');
Route::patch('hris/my-profile/draft', [EmployeeProfileDraftController::class, 'update'])
    ->middleware(['auth', EnsureHrisAccountReady::class])->name('hris.my-profile.draft');
Route::post('hris/my-profile/submit', [EmployeeProfileReviewController::class, 'submit'])
    ->middleware(['auth', EnsureHrisAccountReady::class])->name('hris.my-profile.submit');
Route::post('hris/employees/{employee}/identifiers/{field}', EmployeeIdentifierRevealController::class)
    ->whereNumber('employee')->whereIn('field', ['tin', 'gsis_bp_number'])
    ->middleware(['auth', EnsureHrisAccountReady::class, 'throttle:10,1'])->name('hris.employee.identifier.reveal');

Route::middleware(['auth', EnsureHrisAccountReady::class.':verify-legacy'])->group(function () {
    Route::get('hris/profile-review', [EmployeeProfileReviewController::class, 'index'])->name('hris.profile-review.index');
    Route::put('hris/profile-review/settings', [EmployeeProfileReviewController::class, 'updateSettings'])->name('hris.profile-review.settings');
    Route::post('hris/profile-review/{revision}', [EmployeeProfileReviewController::class, 'decide'])
        ->whereNumber('revision')->middleware('throttle:20,1')->name('hris.profile-review.decide');
    Route::post('hris/profile-review/{revision}/reveal', [EmployeeIdentifierRevealController::class, 'submitted'])
        ->whereNumber('revision')->middleware('throttle:10,1')->name('hris.profile-review.reveal');
    Route::post('hris/employees/{employee}/deactivate', [EmployeeCredentialController::class, 'deactivate'])
        ->whereNumber('employee')->middleware('throttle:10,1')->name('hris.employees.deactivate');
    Route::post('hris/employees/{employee}/reactivate', [EmployeeCredentialController::class, 'reactivate'])
        ->whereNumber('employee')->middleware('throttle:10,1')->name('hris.employees.reactivate');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('hris/plantilla-items/template', [HrisResourceController::class, 'downloadPlantillaWorkbook'])->name('hris.plantilla.template');

    Route::get('hris/account-invitations', [AccountInvitationController::class, 'create'])
        ->middleware('password.confirm')->name('hris.invitations.create');
    Route::post('hris/account-invitations', [AccountInvitationController::class, 'store'])
        ->middleware(['password.confirm', 'throttle:6,1'])->name('hris.invitations.store');
    Route::get('hris/employees/import', [EmployeeImportController::class, 'create'])->name('hris.employees.import');
    Route::get('hris/employees/import/template', [EmployeeImportController::class, 'downloadTemplate'])->name('hris.employees.import.template');
    Route::put('hris/employees/import/template', [EmployeeImportController::class, 'updateTemplate'])->name('hris.employees.import.template.update');
    Route::post('hris/employees/import/preview', [EmployeeImportController::class, 'preview'])->name('hris.employees.import.preview');
    Route::post('hris/employees/import', [EmployeeImportController::class, 'store'])->name('hris.employees.import.store');
    Route::post('hris/employees/{employee}/activation-code', [EmployeeCredentialController::class, 'reissue'])
        ->middleware('throttle:10,1')->whereNumber('employee')->name('hris.employees.activation.reissue');
    Route::post('hris/employees/activation-slips/print', [EmployeeCredentialController::class, 'print'])
        ->name('hris.employees.activation.print');

    Route::prefix('hris')->name('hris.')->group(function () {
        require app_path('Attendance/routes.php');

        Route::get('portal/{page}', PortalController::class)
            ->whereIn('page', ['my-profile', 'my-attendance', 'my-leave-credits', 'my-leave-requests', 'my-payslips', 'staffing', 'plantilla-summary'])
            ->name('portal');
        Route::post('portal/my-leave-requests', [PortalController::class, 'submitLeave'])
            ->name('portal.leave.store');
        Route::get('leave-applications/create', [LeaveApplicationController::class, 'create'])
            ->name('leave-applications.create');
        Route::post('leave-applications/on-behalf', [LeaveApplicationController::class, 'store'])
            ->name('leave-applications.on-behalf');
        Route::post('leave-applications/{leave}/review', LeaveReviewController::class)->name('leave.review');
        Route::get('attendance/logs', [AttendanceLogImportController::class, 'index'])->name('attendance-logs.index');
        Route::post('attendance/logs', [AttendanceLogImportController::class, 'store'])->name('attendance-logs.store');
        Route::delete('attendance/logs/{batch}', [AttendanceLogImportController::class, 'destroy'])->whereNumber('batch')->name('attendance-logs.destroy');
        Route::get('attendance/daily-punches', [DailyPunchController::class, 'index'])->name('attendance.daily-punches.index');
        Route::get('attendance/daily-punches/export', [DailyPunchController::class, 'export'])->name('attendance.daily-punches.export');
        Route::get('attendance/monthly-report', [MonthlyAttendanceReportController::class, 'index'])->name('attendance-report.index');
        Route::put('attendance/monthly-report/{employee}/clock-in-rule', [MonthlyAttendanceReportController::class, 'saveRule'])->whereNumber('employee')->name('attendance-report.clock-in-rule.update');
        Route::put('employees/{employee}/biometric-id', BiometricLinkController::class)->whereNumber('employee')->name('employees.biometric-id.update');
        Route::patch('employees/{employee}/biometric-id/lock', [BiometricLinkController::class, 'setLock'])->whereNumber('employee')->name('employees.biometric-id.lock');
        Route::get('attendance/dtr/demo', [DailyTimeRecordController::class, 'demo'])->name('attendance.dtr.demo');
        Route::get('attendance/dtr/{employee}', DailyTimeRecordController::class)->whereNumber('employee')->name('attendance.dtr');
        Route::get('attendance/corrections', [AttendanceCorrectionController::class, 'index'])->name('attendance-corrections.index');
        Route::post('attendance/corrections', [AttendanceCorrectionController::class, 'store'])->name('attendance-corrections.store');
        Route::post('attendance/corrections/{correction}/review', [AttendanceCorrectionController::class, 'review'])->name('attendance-corrections.review');
        Route::post('attendance/corrections/{correction}/withdraw', [AttendanceCorrectionController::class, 'withdraw'])->name('attendance-corrections.withdraw');
        Route::get('attendance-corrections', fn () => to_route('hris.attendance-corrections.index', request()->query()));
        Route::post('attendance-corrections', [AttendanceCorrectionController::class, 'store']);
        Route::post('attendance-corrections/{correction}/review', [AttendanceCorrectionController::class, 'review']);
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
