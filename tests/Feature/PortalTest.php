<?php

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PlantillaItem;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('employee portal returns only the signed-in employee records', function () {
    $role = Role::factory()->create(['name' => 'Employee']);
    $user = User::factory()->create(['role_id' => $role->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['employee_id' => $employee->id])->save();

    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-11-03']);
    AttendanceRecord::factory()->create(['attendance_date' => '2026-11-04']);

    $this->actingAs($user)->get('/hris/portal/my-attendance')
        ->assertInertia(fn (Assert $page) => $page
            ->component('hris/portal')
            ->has('records.data', 1)
            ->where('records.data.0.0', '2026-11-03'));

    $this->get('/hris/portal/staffing')->assertForbidden();
});

test('mayor staffing shows summary counts and employee pages are forbidden', function () {
    $role = Role::factory()->create(['name' => 'Mayor']);
    $user = User::factory()->create(['role_id' => $role->id]);
    $department = Department::factory()->create(['name' => 'Treasury']);
    $position = Position::factory()->create(['department_id' => $department->id]);
    Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);

    $this->actingAs($user)->get('/hris/portal/staffing')
        ->assertInertia(fn (Assert $page) => $page
            ->component('hris/portal')
            ->has('records.data', 1)
            ->where('records.data.0', ['Treasury', $department->code, 1, 0]));

    PlantillaItem::create([
        'department_id' => $department->id,
        'position_id' => $position->id,
        'item_number' => 'MTO-1',
        'allocated_count' => 4,
        'filled_count' => 1,
        'fiscal_year' => now()->year,
        'status' => 'active',
    ]);

    $this->get('/hris/portal/plantilla-summary')
        ->assertInertia(fn (Assert $page) => $page
            ->where('records.data.0', ['Treasury', 4, 1, 3]));

    $this->get('/hris/portal/my-profile')->assertForbidden();
});

test('employees see only generated payslips for their own released payroll', function () {
    $role = Role::factory()->create(['name' => 'Employee']);
    $user = User::factory()->create(['role_id' => $role->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['employee_id' => $employee->id])->save();

    $released = PayrollPeriod::factory()->create(['code' => 'RELEASED', 'released_at' => now()->subDay()]);
    $unreleased = PayrollPeriod::factory()->create();
    foreach ([[$employee, $released], [$employee, $unreleased], [Employee::factory()->create(), $released]] as [$owner, $period]) {
        $payroll = Payroll::factory()->create([
            'employee_id' => $owner->id,
            'payroll_period_id' => $period->id,
            'status' => 'approved',
        ]);
        Payslip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $owner->id,
            'generated_at' => now(),
        ]);
    }

    $this->actingAs($user)->get('/hris/portal/my-payslips')
        ->assertInertia(fn (Assert $page) => $page
            ->has('records.data', 1)
            ->where('records.data.0.0', 'RELEASED'));
});

test('employees can submit pending leave for themselves and cannot submit overlapping requests', function () {
    $role = Role::factory()->create(['name' => 'Employee']);
    $user = User::factory()->create(['role_id' => $role->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['employee_id' => $employee->id])->save();
    $other = Employee::factory()->create();
    $type = LeaveType::factory()->create(['is_active' => true]);
    $url = '/hris/portal/my-leave-requests';
    $data = [
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-23',
        'end_date' => '2026-11-24',
        'days_requested' => 2,
        'reason' => 'Personal leave',
        'employee_id' => $other->id,
        'status' => 'approved',
    ];

    $this->actingAs($user)->post($url, $data)->assertRedirect($url);
    $this->assertDatabaseHas('leave_applications', [
        'employee_id' => $employee->id,
        'status' => 'pending',
        'approved_by' => null,
    ]);
    $this->post($url, $data)->assertSessionHasErrors('start_date');
    $this->assertDatabaseCount('leave_applications', 1);
    $this->get($url)->assertInertia(fn (Assert $page) => $page
        ->has('records.data', 1)
        ->where('records.data.0.4', 'pending'));
});

test('leave advances through authorized reviewers and charges the balance once', function () {
    $employee = Employee::factory()->create();
    $type = LeaveType::factory()->create(['is_paid' => true]);
    $credit = $employee->leaveCredits()->create([
        'leave_type_id' => $type->id,
        'year' => 2026,
        'earned_days' => 5,
        'used_days' => 0,
        'remaining_days' => 5,
    ]);
    $leave = $employee->leaveApplications()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-23',
        'end_date' => '2026-11-24',
        'days_requested' => 2,
        'reason' => 'Personal leave',
        'status' => 'pending',
    ]);

    $departmentRole = Role::factory()->create(['name' => 'Department Head']);
    $departmentHead = User::factory()->create(['role_id' => $departmentRole->id]);
    $headEmployee = Employee::factory()->create(['user_id' => $departmentHead->id]);
    $departmentHead->forceFill(['employee_id' => $headEmployee->id])->save();
    $officer = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'HR Officer'])->id]);
    $hrmo = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'HRMO Head'])->id]);
    $url = "/hris/leave-applications/{$leave->id}/review";

    $this->actingAs($departmentHead)->post($url, ['decision' => 'advance'])->assertForbidden();
    $headEmployee->update(['department_id' => $employee->department_id]);
    $departmentHead->unsetRelation('employee');
    $this->actingAs($hrmo)->post($url, ['decision' => 'advance'])->assertSessionHasErrors('decision');
    $this->actingAs($departmentHead)->post($url, ['decision' => 'advance'])->assertRedirect();
    expect($leave->refresh()->status)->toBe('department_reviewed');

    $this->actingAs($officer)->post($url, ['decision' => 'advance'])->assertRedirect();
    expect($leave->refresh()->status)->toBe('hr_validated');
    expect($credit->refresh()->remaining_days)->toBe(5);

    $this->actingAs($hrmo)->post($url, ['decision' => 'advance'])->assertRedirect();
    expect($leave->refresh()->status)->toBe('approved');
    expect($leave->approved_by)->toBe($hrmo->id);
    expect($credit->refresh()->remaining_days)->toBe(3);

    $this->post($url, ['decision' => 'advance'])->assertSessionHasErrors('decision');
    $this->put("/hris/leave-applications/{$leave->id}", ['status' => 'pending'])->assertForbidden();
    expect($credit->refresh()->remaining_days)->toBe(3);
    $this->assertDatabaseCount('audit_logs', 3);
});

test('attendance corrections update the time record only after department and HR review', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Employee'])->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['employee_id' => $employee->id])->save();
    $log = AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-11-01', 'time_out' => null]);
    $otherLog = AttendanceRecord::factory()->create(['attendance_date' => '2026-11-01']);
    $path = '/hris/attendance-corrections';
    $data = [
        'attendance_record_id' => $log->id,
        'time_in' => '2026-11-01T08:00',
        'time_out' => '2026-11-01T16:00',
        'reason' => 'Missing time out',
    ];

    $this->actingAs($user)->post($path, array_replace($data, ['attendance_record_id' => $otherLog->id]))->assertNotFound();
    $this->post($path, $data)->assertRedirect($path);
    $this->post($path, $data)->assertSessionHasErrors('attendance_record_id');
    $entry = $log->corrections()->sole();

    $head = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Department Head'])->id]);
    $headEmployee = Employee::factory()->create(['user_id' => $head->id, 'department_id' => $employee->department_id]);
    $head->forceFill(['employee_id' => $headEmployee->id])->save();
    $hr = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'HR Officer'])->id]);
    $review = "{$path}/{$entry->id}/review";

    $this->actingAs($hr)->post($review, ['decision' => 'advance'])->assertSessionHasErrors('decision');
    $this->actingAs($head)->post($review, ['decision' => 'advance'])->assertRedirect();
    expect($entry->refresh()->status)->toBe('department_reviewed');
    expect($log->refresh()->time_out)->toBeNull();
    $this->actingAs($hr)->post($review, ['decision' => 'advance'])->assertRedirect();
    expect($entry->refresh()->status)->toBe('approved');
    expect($log->refresh()->time_out)->toBe('2026-11-01 16:00:00');
    expect((float) $log->total_hours)->toBe(8.0);
    $this->assertDatabaseCount('audit_logs', 2);

    $missing = array_replace($data, [
        'attendance_record_id' => null,
        'time_in' => '2026-11-02T08:00',
        'time_out' => '2026-11-02T16:00',
    ]);
    $this->actingAs($user)->post($path, $missing)->assertRedirect($path);
    $this->post($path, $missing)->assertSessionHasErrors('attendance_record_id');
    $request = $employee->attendanceCorrections()->whereNull('attendance_record_id')->sole();
    $missingReview = "{$path}/{$request->id}/review";
    expect($employee->attendanceRecords()->where('attendance_date', '2026-11-02')->exists())->toBeFalse();

    $this->actingAs($head)->post($missingReview, ['decision' => 'advance'])->assertRedirect();
    expect($request->refresh()->attendance_record_id)->toBeNull();

    $this->actingAs($hr)->post($missingReview, ['decision' => 'advance'])->assertRedirect();
    $newLog = $employee->attendanceRecords()->where('attendance_date', '2026-11-02')->sole();
    expect($request->refresh()->attendance_record_id)->toBe($newLog->id);
    expect($newLog->source)->toBe('approved_correction');
    $this->post($missingReview, ['decision' => 'advance'])->assertSessionHasErrors('decision');

});
