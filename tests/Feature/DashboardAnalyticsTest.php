<?php

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 11, 18)->setTime(12, 0));
});

test('employee dashboard charts and records contain only their own data', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Employee'])->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['employee_id' => $employee->id])->save();
    $other = Employee::factory()->create(['first_name' => 'Private other employee']);
    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-11-03', 'status' => 'present']);
    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-08-03', 'status' => 'absent']);
    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-12-03', 'status' => 'late']);
    AttendanceRecord::factory()->create(['employee_id' => $other->id, 'attendance_date' => '2026-11-04', 'status' => 'late']);
    $leave = LeaveApplication::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-21']);
    LeaveApplication::factory()->create(['employee_id' => $other->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-21']);

    $this->actingAs($user)->get('/dashboard?months=3')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('analytics.period.months', 3)
        ->has('analytics.bars.data', 3)
        ->where('analytics.bars.data.2.primary', 1)
        ->where('analytics.bars.data.2.secondary', 0)
        ->where('analytics.bars.data.2.tertiary', 0)
        ->has('analytics.distribution.data', 1)
        ->where('analytics.distribution.data.0.value', 1)
        ->where('analytics.activity.data.2.value', 1)
        ->has('analytics.records.data', 1)
        ->where('analytics.records.data.0.id', 'leave-'.$leave->id)
        ->where('analytics.records.data.0.href', '/hris/portal/my-leave-requests'));
});

test('department dashboard charts are scoped to the linked head department', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Department Head'])->id]);
    $office = Department::factory()->create();
    $head = Employee::factory()->create(['user_id' => $user->id, 'department_id' => $office->id]);
    $user->forceFill(['employee_id' => $head->id])->save();
    $staff = Employee::factory()->create(['department_id' => $office->id]);
    AttendanceRecord::factory()->create(['employee_id' => $staff->id, 'attendance_date' => '2026-11-02', 'status' => 'present']);
    AttendanceRecord::factory()->create(['attendance_date' => '2026-11-02', 'status' => 'absent']);
    $leave = LeaveApplication::factory()->create(['employee_id' => $staff->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-21']);
    LeaveApplication::factory()->create(['start_date' => '2026-11-20', 'end_date' => '2026-11-21']);

    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('analytics.scope', $office->name)
        ->where('analytics.distribution.data.0.value', 2)
        ->where('analytics.bars.data.5.primary', 1)
        ->where('analytics.bars.data.5.tertiary', 0)
        ->has('analytics.records.data', 1)
        ->where('analytics.records.data.0.id', 'leave-'.$leave->id));
});

test('a mismatched employee link cannot expose department or personal charts', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Department Head'])->id]);
    $otherUser = User::factory()->create();
    $other = Employee::factory()->create(['user_id' => $otherUser->id]);
    $user->forceFill(['employee_id' => $other->id])->save();
    AttendanceRecord::factory()->create(['employee_id' => $other->id, 'attendance_date' => '2026-11-03']);
    LeaveApplication::factory()->create(['employee_id' => $other->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-21']);

    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('analytics.distribution.data', [])
        ->where('analytics.records.data', [])
        ->where('analytics.bars.data.5.primary', 0)
        ->where('cards.0.value', '0'));
});

test('reporting periods are validated and supported periods build complete month series', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'HRMO Head'])->id]);
    $this->actingAs($user)->get('/dashboard?months=12')->assertInertia(fn (Assert $page) => $page
        ->where('analytics.period.months', 12)->has('analytics.bars.data', 12)->has('analytics.activity.data', 12));
    $this->get('/dashboard?months=99')->assertSessionHasErrors('months');
});

test('mis dashboard reports system activity without personnel records', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'MIS Administrator'])->id]);
    LeaveApplication::factory()->create(['start_date' => '2026-11-20', 'end_date' => '2026-11-21']);
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('analytics.scope', 'System administration')
        ->where('analytics.distribution.title', 'Account status')
        ->where('analytics.records.title', 'Recent sign-in activity')
        ->where('analytics.records.data', []));
});

test('notifications are private and only their owner can mark them as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $first = Notification::create(['user_id' => $user->id, 'title' => 'First update', 'message' => 'Your record was updated.']);
    $second = Notification::create(['user_id' => $user->id, 'title' => 'Second update', 'message' => 'A new update is available.']);
    $private = Notification::create(['user_id' => $other->id, 'title' => 'Private update', 'message' => 'Another account update.']);

    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('notifications.unreadCount', 2)->has('notifications.items', 2));
    $this->post('/notifications/'.$private->id.'/read')->assertNotFound();
    $this->from('/dashboard')->post('/notifications/'.$first->id.'/read')->assertRedirect('/dashboard');
    expect($first->refresh()->is_read)->toBeTrue();
    expect($first->read_at)->not->toBeNull();
    expect($second->refresh()->is_read)->toBeFalse();
    $this->from('/dashboard')->post('/notifications/read-all')->assertRedirect('/dashboard');
    expect($second->refresh()->is_read)->toBeTrue();
    expect($private->refresh()->is_read)->toBeFalse();
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('notifications.unreadCount', 0));
});
