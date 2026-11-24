<?php

use App\Attendance\Controllers\EmployeeDayController;
use App\Attendance\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('attendance/employees/{employee}/days/{date}', EmployeeDayController::class)
    ->whereNumber('employee')->name('attendance.employee-day');

Route::prefix('attendance/{kind}/{owner}')->middleware('can:manage-attendance-schedules')
    ->where(['kind' => 'department-schedules|employee-overrides', 'owner' => '[0-9]+'])
    ->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('attendance.schedules.index');
        Route::post('/', [ScheduleController::class, 'store'])->name('attendance.schedules.store');
        Route::put('{schedule}', [ScheduleController::class, 'update'])->whereNumber('schedule')->name('attendance.schedules.update');
        Route::delete('{schedule}', [ScheduleController::class, 'destroy'])->whereNumber('schedule')->name('attendance.schedules.destroy');
    });
