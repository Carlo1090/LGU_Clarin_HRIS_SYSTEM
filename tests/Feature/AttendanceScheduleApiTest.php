<?php

use App\Attendance\Models\DepartmentSchedule;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('HR can manage effective schedules with an audit trail and recomputed punches', function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 11, 25));
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $worker = User::factory()->for(Role::factory()->state(['name' => 'Employee']))->create();
    $employee = Employee::factory()->create(['biometric_id' => 15]);
    $department = "/hris/attendance/department-schedules/{$employee->department_id}";
    $override = "/hris/attendance/employee-overrides/{$employee->id}";
    $day = "/hris/attendance/employees/{$employee->id}/days/2026-11-24";
    $schedule = [
        'am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00',
        'grace_minutes' => 0, 'crosses_midnight' => false, 'effective_from' => '2026-11-24',
    ];

    $this->actingAs($worker)->getJson($department)->assertForbidden();
    $this->postJson($department, $schedule)->assertForbidden();
    $this->actingAs($hr)->post('/hris/attendance/logs', [
        'file' => UploadedFile::fake()->createWithContent('morning.dat', "15\t2026-11-24 08:10:00\t1\t3\t1\t0\n15\t2026-11-24 08:11:00\t1\t0\t1\t0\n"),
    ])->assertRedirect();
    $this->getJson($day)->assertOk()->assertJsonPath('status', 'no_schedule');
    $this->postJson($department, array_replace($schedule, ['pm_out' => '11:00']))->assertUnprocessable();

    $id = $this->postJson($department, $schedule)->assertCreated()->json('id');
    $this->getJson($department)->assertOk()->assertJsonPath('0.id', $id);
    $this->get('/hris/departments')->assertInertia(fn ($page) => $page
        ->where('canManageSchedules', true)
        ->where('records.data', fn ($rows) => collect($rows)->firstWhere('id', $employee->department_id)['work_schedule'] === '08:00-12:00 / 13:00-17:00'));
    $this->get('/hris/employees')->assertInertia(fn ($page) => $page
        ->where('records.data.0.work_schedule', 'From '.$employee->department->name));
    $this->assertDatabaseHas('attendance_daily', ['employee_id' => $employee->id, 'schedule_id' => $id, 'late_minutes_am' => 10]);
    $this->getJson($day)->assertOk()->assertJsonPath('schedule_source', 'department')
        ->assertJsonPath('punches.0.raw_status', 3)->assertJsonPath('punches.0.slot', 'am_in')
        ->assertJsonPath('punches.1.merged_duplicate', true)->assertJsonPath('punches.1.slot', null);

    $oid = $this->postJson($override, $schedule + ['reason' => 'Temporary schedule'])->assertCreated()->json('id');
    $this->putJson("$override/$oid", array_replace($schedule, ['grace_minutes' => 10, 'reason' => 'Approved grace']))->assertOk();
    $this->getJson($day)->assertJsonPath('schedule_source', 'override')->assertJsonPath('late_minutes_am', 0);
    $this->get('/hris/employees')->assertInertia(fn ($page) => $page
        ->where('records.data.0.work_schedule', 'Override'));
    $audit = AuditLog::query()->where('action', 'attendance.schedule.updated')->sole();
    expect(json_decode($audit->metadata, true)['old']['reason'])->toBe('Temporary schedule');
    expect(json_decode($audit->metadata, true)['new']['reason'])->toBe('Approved grace');
    $this->deleteJson("$override/$oid")->assertNoContent();
    $this->assertDatabaseHas('attendance_daily', ['employee_id' => $employee->id, 'schedule_source' => 'department', 'late_minutes_am' => 10]);
    $this->deleteJson("$department/$id")->assertNoContent();
    expect(DepartmentSchedule::query()->count())->toBe(0);
    $this->getJson($day)->assertJsonPath('status', 'no_schedule')->assertJsonPath('schedule_source', null);
});
