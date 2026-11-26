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
            ->where('hrisNavigation.0.href', '/hris/portal/my-attendance')
            ->where('hrisNavigation.1.href', '/hris/portal/my-leave-requests')
            ->where('dtrPreviewUrl', "/hris/attendance/dtr/{$employee->id}")
            ->where('dtrDemoUrl', '/hris/attendance/dtr/demo')
            ->where('leaveApplicant', null)
            ->has('records.data', 1)
            ->where('records.data.0.0', '2026-11-03'));

    $this->get('/hris/portal/staffing')->assertForbidden();
});

test('HR navigation includes attendance records and omits a separate corrections entry', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();

    $this->actingAs($hr)->get('/hris/attendance')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hrisNavigation.0.href', '/hris/attendance')
            ->where('hrisNavigation.1.href', '/hris/employees')
            ->where('hrisNavigation.2.href', '/hris/leave-applications')
            ->where('canViewAttendanceCorrections', true)
            ->where('hrisNavigation', fn ($items) => ! collect($items)->contains('title', 'Attendance corrections')));

    $this->get('/hris/attendance-corrections')->assertRedirect('/hris/attendance/corrections');
});

test('DTR preview returns the selected cutoff and respects attendance access', function () {
    $this->withoutVite();

    $employeeRole = Role::factory()->create(['name' => 'Employee']);
    $user = User::factory()->create(['role_id' => $employeeRole->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['employee_id' => $employee->id])->save();
    $other = Employee::factory()->create();

    foreach (['2026-10-15', '2026-10-16', '2026-11-01'] as $date) {
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => $date,
            'time_in' => $date.' 07:35:21',
            'time_out' => null,
            'status' => 'on_leave',
        ]);
    }

    $url = "/hris/attendance/dtr/{$employee->id}?month=2026-10&period=second";
    $this->actingAs($user)->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('hris/daily-time-record')
            ->where('periodLabel', 'October 16–31, 2026')
            ->has('records', 1)
            ->where('records.16.amIn', '07:35')
            ->where('records.16.pmOut', null)
            ->where('records.16.remarks', 'On leave'));
    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('period', 'second')
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.16.amIn', '07:35');
    $this->getJson("/hris/attendance/dtr/{$employee->id}?period=invalid")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('period');
    $this->get("/hris/attendance/dtr/{$other->id}")->assertForbidden();
    $this->getJson("/hris/attendance/dtr/{$other->id}")->assertForbidden();

    $departmentHeadRole = Role::factory()->create(['name' => 'Department Head']);
    $head = User::factory()->create(['role_id' => $departmentHeadRole->id, 'employee_id' => $employee->id]);
    $this->actingAs($head)->get($url)->assertOk();
    $this->get("/hris/attendance/dtr/{$other->id}")->assertForbidden();

    $hrRole = Role::factory()->create(['name' => 'HR Officer']);
    $hr = User::factory()->create(['role_id' => $hrRole->id]);
    $this->actingAs($hr)->get($url)->assertOk();
});

test('DTR demo shows a sample workday, undertime and leave without creating attendance', function () {
    $this->withoutVite();
    $hr = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'HR Officer'])->id]);

    $this->actingAs($hr)->get('/hris/attendance/dtr/demo')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('hris/daily-time-record')
            ->where('demo', true)
            ->where('officialHours', '08:00–12:00 / 13:00–17:00')
            ->where('records.19', ['amIn' => '07:55', 'amOut' => '12:02', 'pmIn' => '12:58', 'pmOut' => '17:05'])
            ->where('records.20.undertimeHours', 0)
            ->where('records.20.undertimeMinutes', 20)
            ->where('records.21.remarks', 'PM vacation leave')
            ->missing('records.21.pmIn'));
    $this->assertDatabaseCount('attendance_records', 0);
    $this->getJson('/hris/attendance/dtr/demo')
        ->assertOk()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('records.20.undertimeMinutes', 20);

    $other = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Accountant'])->id]);
    $this->actingAs($other)->getJson('/hris/attendance/dtr/demo')->assertForbidden();
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
        ->where('leaveApplicant.firstName', $employee->first_name)
        ->where('leaveApplicant.lastName', $employee->last_name)
        ->where('leaveTypes.0.id', $type->id)
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

test('activated employees and HR officers file slot corrections for HRMO decision without editing punches', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-26 12:00:00'));
    $role = Role::factory()->create(['name' => 'Employee']);
    $user = User::factory()->for($role)->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'last_name' => 'Zulu', 'biometric_id' => 1090, 'date_hired' => '2020-01-01']);
    $user->forceFill(['employee_id' => $employee->id])->save();
    $other = Employee::factory()->create(['last_name' => 'Adams']);
    $officer = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $hrmo = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    \App\Models\AuditLog::create(['user_id' => $user->id, 'action' => 'employee.activation_completed']);
    \App\Attendance\Models\DepartmentSchedule::create([
        'department_id' => $employee->department_id,
        'am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00',
        'effective_from' => '2026-11-01',
    ]);
    $batch = \App\Models\AttendanceImport::create(['file_name' => 'test.dat', 'status' => 'completed']);
    foreach (['08:00:00', '12:00:00', '17:00:00'] as $time) {
        \App\Models\AttendancePunch::create([
            'attendance_import_id' => $batch->id, 'import_batch_id' => $batch->id,
            'biometric_id' => 1090, 'machine_user_id' => 1090, 'device_id' => 1,
            'punched_at' => '2026-11-18 '.$time, 'created_at' => now(),
        ]);
    }
    $raw = \App\Models\AttendancePunch::orderBy('id')->get()->toArray();
    $path = '/hris/attendance/corrections';
    $data = [
        'work_date' => '2026-11-18',
        'slot' => 'pm_in',
        'proposed_at' => '2026-11-18T13:05',
        'reason' => 'Missed lunch return punch',
        'evidence' => 'Memo 18',
    ];

    $this->actingAs($user)->get('/hris/portal/my-leave-credits')->assertRedirect('/hris/my-profile');
    $this->get('/hris/portal/my-attendance')->assertInertia(fn (Assert $page) => $page->where('correctionsUrl', $path));
    $this->get($path."?employee={$other->id}")->assertForbidden();
    $this->get($path)->assertInertia(fn (Assert $page) => $page
        ->where('employeeId', $employee->id)
        ->has('employees', 0)
        ->where('flagged.2026-11-18', 'incomplete')
        ->has('punches.2026-11-18', 3));
    $this->post($path, $data + ['employee' => $other->id])->assertForbidden();
    $this->post($path, array_replace($data, ['reason' => '']))->assertSessionHasErrors('reason');
    $this->post($path, array_replace($data, ['evidence' => '']))->assertSessionHasErrors('evidence');
    $this->post($path, array_replace($data, ['work_date' => '2026-11-19', 'proposed_at' => '2026-11-19T13:05']))
        ->assertSessionHasErrors('work_date');
    $this->post($path, $data)->assertRedirect($path.'?month=2026-11');
    $this->post($path, $data)->assertSessionHasErrors('slot');
    $withdrawn = $employee->attendanceCorrections()->sole();
    $withdrawUrl = "{$path}/{$withdrawn->id}/withdraw";
    $this->actingAs($officer)->post($withdrawUrl)->assertForbidden();
    $this->actingAs($user)->post($withdrawUrl)->assertRedirect();
    $this->post($withdrawUrl)->assertSessionHasErrors('withdraw');
    $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_correction.withdrawn', 'user_id' => $user->id, 'model_id' => $withdrawn->id]);
    $this->post($path, $data)->assertRedirect();
    $original = $employee->attendanceCorrections()->where('status', 'pending')->sole();
    $review = "{$path}/{$original->id}/review";
    $this->post($review, ['decision' => 'approve'])->assertRedirect('/hris/my-profile');
    $this->actingAs($officer)->post($review, ['decision' => 'approve'])->assertForbidden();
    $this->get($path.'?employee=&month=2026-11')->assertInertia(fn (Assert $page) => $page
        ->where('employeeId', null)
        ->where('employees.0.id', $other->id)
        ->where('employees.1.id', $employee->id)
        ->where('records.data.0.id', $original->id));
    $this->get($path.'?month=2026-10')->assertInertia(fn (Assert $page) => $page
        ->has('records.data', 0));

    $this->actingAs($hrmo)->post($path, $data + ['employee' => $employee->id])->assertForbidden();
    $this->get($path.'?request='.$original->id)->assertInertia(fn (Assert $page) => $page
        ->where('selected.canReview', true)->where('selected.evidence', 'Memo 18')
        ->where('selected.hasRawSlot', false)->has('selected.punches', 3));
    $this->post($review, ['decision' => 'return'])->assertSessionHasErrors('comment');
    $this->post($review, ['decision' => 'return', 'comment' => 'Please confirm the return time.'])->assertRedirect();
    $this->actingAs($user)->get($path.'?request='.$original->id)->assertInertia(fn (Assert $page) => $page
        ->where('selected.status', 'returned')->where('selected.comment', 'Please confirm the return time.'));

    $this->post($path, array_replace($data, ['slot' => 'am_in', 'proposed_at' => '2026-11-18T08:05', 'evidence' => '']))->assertRedirect();
    $rejected = $employee->attendanceCorrections()->where('status', 'pending')->sole();
    $this->actingAs($hrmo)->post("{$path}/{$rejected->id}/review", ['decision' => 'reject'])->assertRedirect();
    $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_correction.rejected', 'user_id' => $hrmo->id, 'model_id' => $rejected->id]);

    $officerData = $data + ['employee' => $employee->id];
    $this->actingAs($officer)->post($path, $officerData)->assertRedirect();
    $entry = $employee->attendanceCorrections()->where('status', 'pending')->sole();
    $this->assertSame($officer->id, $entry->filed_by);
    $attendanceUrl = '/hris/attendance?month=2026-11&q='.$employee->employee_number;
    $this->actingAs($hrmo)->get($attendanceUrl)
        ->assertInertia(fn (Assert $page) => $page
            ->where('records.data.0.requests_pending', 1)
            ->where('records.data.0.review_days.0.state', 'Requested'));
    $entry->update(['filed_by' => $hrmo->id]);
    $this->post("{$path}/{$entry->id}/review", ['decision' => 'approve'])->assertForbidden();
    $entry->update(['filed_by' => $officer->id, 'proposed_at' => '2026-11-18 18:00:00']);
    $this->post("{$path}/{$entry->id}/review", ['decision' => 'approve'])->assertSessionHasErrors('decision');
    expect($entry->refresh()->status)->toBe('pending');
    $entry->update(['proposed_at' => '2026-11-18 13:05:00']);
    $this->post("{$path}/{$entry->id}/review", ['decision' => 'approve'])->assertRedirect();
    $this->post("{$path}/{$entry->id}/review", ['decision' => 'approve'])->assertSessionHasErrors('decision');
    $this->assertDatabaseHas('attendance_daily', [
        'employee_id' => $employee->id, 'work_date' => '2026-11-18',
        'status' => 'complete', 'pm_in_at' => '2026-11-18 13:05:00', 'late_minutes_pm' => 5,
    ]);
    $this->assertDatabaseCount('attendance_records', 0);
    $this->assertSame($raw, \App\Models\AttendancePunch::orderBy('id')->get()->toArray());
    $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_correction.filed', 'user_id' => $officer->id, 'model_id' => $entry->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_correction.returned', 'user_id' => $hrmo->id, 'model_id' => $original->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_correction.approved', 'user_id' => $hrmo->id, 'model_id' => $entry->id]);

    $this->get($attendanceUrl)->assertInertia(fn (Assert $page) => $page
        ->where('records.data.0.attendance_review_days', 0)
        ->where('records.data.0.requests_pending', 0)
        ->where('records.data.0.review_days.0.state', 'Approved'));
    $this->get('/hris/attendance/daily-punches?start_date=2026-11-18&end_date=2026-11-18&employee='.$employee->id)
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.data.0.correction_times.PM_IN', '13:05:00')->has('rows.data.0.punches', 3));

    $this->actingAs($user)->get($path.'?request='.$entry->id)
        ->assertInertia(fn (Assert $page) => $page->where('selected.status', 'approved'));
    $this->getJson("/hris/attendance/dtr/{$employee->id}?month=2026-11")
        ->assertOk()
        ->assertJsonPath('records.18.pmIn', '13:05')
        ->assertJsonPath('records.18.correctionNotes.pm_in', "PM in 13:05 corrected by {$hrmo->name} on 2026-11-26");
});
