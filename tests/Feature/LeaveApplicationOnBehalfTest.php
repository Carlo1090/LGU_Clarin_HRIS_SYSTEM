<?php

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('HRMO Head can open the leave form with only employees without linked accounts', function () {
    $hrmo = User::factory()->create([
        'role_id' => Role::factory()->create(['name' => 'HRMO Head'])->id,
    ]);
    $employee = Employee::factory()->create();
    Employee::factory()->create(['user_id' => User::factory()->create()->id]);
    $type = LeaveType::factory()->create(['is_active' => true]);
    LeaveType::factory()->create(['is_active' => false]);

    $this->actingAs($hrmo)->get('/hris/leave-applications/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('hris/leave-applications/create')
            ->has('employees', 1)
            ->where('employees.0.id', $employee->id)
            ->where('leaveTypes.0.id', $type->id));

    $officer = User::factory()->create([
        'role_id' => Role::factory()->create(['name' => 'HR Officer'])->id,
    ]);
    $this->actingAs($officer)->get('/hris/leave-applications/create')->assertForbidden();
    $this->post('/hris/leave-applications/on-behalf')->assertForbidden();
});

test('HRMO Head can create an on-behalf leave request for an unlinked employee', function () {
    $hrmo = User::factory()->create([
        'role_id' => Role::factory()->create(['name' => 'HRMO Head'])->id,
    ]);
    $employee = Employee::factory()->create();
    $linkedEmployee = Employee::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);
    $type = LeaveType::factory()->create(['is_active' => true]);
    $payload = [
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-23',
        'end_date' => '2026-11-24',
        'days_requested' => 2,
        'reason' => 'Abroad',
        'reason_details' => 'Japan',
        'commutation' => true,
    ];

    $this->actingAs($hrmo)->post('/hris/leave-applications/on-behalf', $payload)
        ->assertRedirect('/hris/leave-applications');

    $leave = LeaveApplication::query()->sole();
    expect($leave->employee_id)->toBe($employee->id)
        ->and($leave->status)->toBe('pending')
        ->and($leave->reason)->toBe('Abroad - Japan')
        ->and($leave->commutation)->toBeTrue();
    expect(AuditLog::query()->sole()->user_id)->toBe($hrmo->id);
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'leave.created_on_behalf',
        'model_type' => LeaveApplication::class,
        'model_id' => $leave->id,
    ]);

    $this->post('/hris/leave-applications/on-behalf', $payload)
        ->assertSessionHasErrors('start_date');
    $this->post('/hris/leave-applications/on-behalf', array_replace($payload, [
        'employee_id' => $linkedEmployee->id,
    ]))->assertSessionHasErrors('employee_id');
    $this->post('/hris/leave-applications/on-behalf', array_replace($payload, [
        'reason_details' => '',
    ]))->assertSessionHasErrors('reason_details');
    $this->assertDatabaseCount('leave_applications', 1);
});
