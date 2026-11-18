<?php

use App\Models\AttendanceHoliday;
use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AttendanceSchedule;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Services\WorkCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Date::parse('2026-10-22 19:00:00', 'Asia/Manila'));
});

test('HR can save effective-dated work schedules and holidays with employee and office precedence', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $employee = Employee::factory()->create();
    $url = '/hris/attendance/work-calendar';
    $data = [
        'scope' => 'municipal', 'effective_from' => '2026-10-19', 'workdays' => [1, 2, 3, 4, 5],
        'am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00',
    ];

    $this->actingAs($hr)->from($url)->post($url.'/schedules', $data)->assertSessionHasNoErrors()->assertRedirect($url);
    $this->post($url.'/schedules', [
        'scope' => 'office', 'department_id' => $employee->department_id, 'effective_from' => '2026-10-20', 'am_in' => '07:30',
    ] + $data)->assertSessionHasNoErrors();
    $this->post($url.'/schedules', [
        'scope' => 'employee', 'employee_id' => $employee->id, 'effective_from' => '2026-10-21', 'effective_until' => '2026-10-21',
        'am_in' => '09:00', 'pm_out' => '18:00',
    ] + $data)->assertSessionHasNoErrors();
    $this->post($url.'/schedules', ['pm_in' => '11:30'] + $data)->assertSessionHasErrors('pm_in');
    $this->post($url.'/holidays', ['date' => '2026-10-22', 'name' => 'Test municipal holiday'])->assertSessionHasNoErrors();

    $calendar = new WorkCalendar(collect([$employee]), '2026-10-18', '2026-10-25');
    expect($calendar->scheduleOn($employee, '2026-10-18'))->toBeNull()
        ->and($calendar->scheduleOn($employee, '2026-10-19')->am_in)->toBe('08:00')
        ->and($calendar->scheduleOn($employee, '2026-10-20')->am_in)->toBe('07:30')
        ->and($calendar->scheduleOn($employee, '2026-10-21')->am_in)->toBe('09:00')
        ->and($calendar->scheduleOn($employee, '2026-10-22')->am_in)->toBe('07:30')
        ->and($calendar->workingDates($employee, '2026-10-22', '2026-10-25'))->toBe(['2026-10-23']);
    $this->get($url)->assertInertia(fn (Assert $page) => $page
        ->component('hris/work-calendar')->has('schedules', 3)->where('holidays.0.name', 'Test municipal holiday'));
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'attendance.schedule_saved']);

    $head = User::factory()->for(Role::factory()->state(['name' => 'Department Head']))->create();
    $this->actingAs($head)->get($url)->assertForbidden();
    $this->post($url.'/schedules', $data)->assertForbidden();
    $this->post($url.'/holidays', ['date' => '2026-10-22', 'name' => 'Other'])->assertForbidden();
});

test('DTR and attendance badges distinguish timely work, undertime, missing logs, holidays and future workdays', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $timely = Employee::factory()->create(['last_name' => 'Adams', 'date_hired' => '2020-01-01', 'employment_status' => 'active']);
    $review = Employee::factory()->create(['last_name' => 'Zulu', 'date_hired' => '2020-01-01', 'employment_status' => 'active']);
    $timely->forceFill(['biometric_id' => 15])->save();
    $review->forceFill(['biometric_id' => 16])->save();
    $url = '/hris/attendance/work-calendar';
    $data = [
        'scope' => 'municipal', 'effective_from' => '2026-10-19', 'workdays' => [1, 2, 3, 4, 5],
        'am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00',
    ];
    $this->actingAs($hr)->from($url)->post($url.'/schedules', $data)->assertSessionHasNoErrors();
    $this->post($url.'/holidays', ['date' => '2026-10-21', 'name' => 'Local holiday'])->assertSessionHasNoErrors();
    $batch = AttendanceImport::query()->create(['file_name' => 'calendar.dat', 'status' => 'completed']);
    foreach ([15 => ['19', '20', '22'], 16 => ['19']] as $id => $dates) {
        foreach ($dates as $date) {
            $times = $id === 15 ? ['07:55', '12:02', '12:59', '17:01'] : ['08:10', '12:00', '13:05', '16:40'];
            foreach ($times as $time) {
                AttendancePunch::query()->insert([
                    'attendance_import_id' => $batch->id, 'biometric_id' => $id,
                    'punched_at' => "2026-10-{$date} {$time}:00", 'created_at' => now(),
                ]);
            }
        }
    }

    $this->get('/hris/attendance?month=2026-10')->assertInertia(fn (Assert $page) => $page
        ->where('records.data.0.attendance_perfect', true)
        ->where('records.data.0.attendance_complete_days', 3)
        ->where('records.data.1.attendance_perfect', false)
        ->where('records.data.1.attendance_missing_days', 2)
        ->where('records.data.1.attendance_review_days', 3));
    $this->getJson("/hris/attendance/dtr/{$review->id}?month=2026-10")->assertOk()
        ->assertJsonPath('officialHours', '08:00–12:00 / 13:00–17:00')
        ->assertJsonPath('records.19.remarks', 'Late 15 min')
        ->assertJsonPath('records.19.undertimeMinutes', 20)
        ->assertJsonPath('records.20.remarks', 'Missing log')
        ->assertJsonPath('records.21.remarks', 'Holiday · Local holiday')
        ->assertJsonMissingPath('records.23')
        ->assertJsonPath('records.24.remarks', 'Rest day');
});

test('leave uses scheduled dates and approved leave covers only those dates on the DTR', function () {
    $user = User::factory()->for(Role::factory()->state(['name' => 'Employee']))->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'date_hired' => '2020-01-01']);
    $user->forceFill(['employee_id' => $employee->id])->save();
    $type = LeaveType::factory()->create(['is_active' => true]);
    AttendanceSchedule::query()->create([
        'scope_key' => 'municipal', 'effective_from' => '2026-10-19', 'workdays' => [1, 2, 3, 4, 5],
        'am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00',
    ]);
    AttendanceHoliday::query()->create(['date' => '2026-10-26', 'name' => 'Test holiday']);
    $this->actingAs($user)->post('/hris/portal/my-leave-requests', [
        'leave_type_id' => $type->id, 'start_date' => '2026-10-23', 'end_date' => '2026-10-27', 'reason' => 'Calendar example',
    ])->assertSessionHasNoErrors()->assertRedirect();
    $leave = $employee->leaveApplications()->sole();
    expect((int) $leave->days_requested)->toBe(2)
        ->and($leave->working_dates)->toBe(['2026-10-23', '2026-10-27']);
    $leave->update(['status' => 'approved']);
    $this->getJson("/hris/attendance/dtr/{$employee->id}?month=2026-10")->assertOk()
        ->assertJsonPath('records.23.remarks', 'Approved leave')
        ->assertJsonPath('records.24.remarks', 'Rest day')
        ->assertJsonPath('records.26.remarks', 'Holiday · Test holiday')
        ->assertJsonPath('records.27.remarks', 'Approved leave');
});
