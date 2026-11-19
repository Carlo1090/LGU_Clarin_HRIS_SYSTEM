<?php

use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('attendance logs preserve raw values, count invalid rows and overlap, and group punches chronologically', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $url = '/hris/attendance/logs';
    $file = "     15\t2026-10-19 12:00:00\t1\t2\t1\t0\r\n\r\n15\t2026-10-19 07:10:00\r\n7\t2026-10-18 17:00:00\t0\t1\t2\t3\r\n15\t2026-02-30 07:00:00\r\n0\t2026-10-19 07:00:00\r\n";

    $this->actingAs($hr)->from($url)->post($url, [
        'file' => UploadedFile::fake()->createWithContent('attlog.dat', $file),
        'clock_offset_seconds' => 60,
    ])->assertSessionHasNoErrors()->assertRedirect();
    $batch = AttendanceImport::query()->sole();
    expect($batch->total_lines)->toBe(5)
        ->and($batch->inserted_count)->toBe(3)
        ->and($batch->invalid_count)->toBe(2)
        ->and(array_column($batch->invalid_lines, 'line'))->toBe([5, 6]);
    $this->assertDatabaseHas('attendance_punches', [
        'biometric_id' => 15, 'punched_at' => '2026-10-19 12:01:00',
        'raw_3' => 1, 'raw_4' => 2, 'raw_5' => 1, 'raw_6' => 0,
    ]);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'attendance.logs_imported', 'model_id' => $batch->id]);

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('renamed.dat', $file)])
        ->assertSessionHasErrors('file');
    $this->assertDatabaseCount('attendance_imports', 1);

    $this->post($url, [
        'file' => UploadedFile::fake()->createWithContent('overlap.dat', "15\t2026-10-19 12:00:00\t2\t2\t1\t0\n15\t2026-10-20 17:00:00\n"),
        'clock_offset_seconds' => 60,
    ])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('attendance_imports', ['file_name' => 'overlap.dat', 'inserted_count' => 1, 'duplicate_count' => 1]);
    $this->assertDatabaseCount('attendance_punches', 4);

    $this->get($url.'?unassigned=1&biometric_id=15')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('hris/import-attendance-logs')
        ->has('groups.data', 2)
        ->where('groups.data.0.biometric_id', 7)
        ->where('groups.data.1.biometric_id', 15)
        ->where('groups.data.1.punch_count', 3)
        ->where('punches.data.0.punched_at', '2026-10-19 07:11:00')
        ->where('punches.data.1.punched_at', '2026-10-19 12:01:00')
        ->where('punches.data.2.punched_at', '2026-10-20 17:01:00'));
});

test('removing an import deletes its punches and preserves attendance from other sources', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $head = User::factory()->for(Role::factory()->state(['name' => 'Department Head']))->create();
    $employee = Employee::factory()->create(['date_hired' => '2020-01-01', 'biometric_id' => 15, 'biometric_locked' => true]);
    $otherEmployee = Employee::factory()->create(['date_hired' => '2020-01-01', 'biometric_id' => 16]);
    $manual = AttendanceRecord::factory()->for($employee)->create();
    $url = '/hris/attendance/logs';
    $file = "15\t2026-10-19 08:00:00\n16\t2026-10-19 08:00:00\n";

    $this->actingAs($hr)->post($url, ['file' => UploadedFile::fake()->createWithContent('first.dat', $file)])->assertSessionHasNoErrors();
    $batch = AttendanceImport::query()->sole();
    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('second.dat', "16\t2026-10-20 08:00:00\n")])->assertSessionHasNoErrors();
    $this->get($url.'?batch='.$batch->id)->assertInertia(fn (Assert $page) => $page->where('batch.id', $batch->id));
    expect($employee->refresh()->biometric_status)->toBe('verified');

    $deleteUrl = $url.'/'.$batch->id;
    $this->actingAs($head)->delete($deleteUrl)->assertForbidden();
    $this->actingAs($hr)->delete($deleteUrl)->assertRedirect($url);
    $this->delete($deleteUrl)->assertNotFound();

    $this->assertDatabaseMissing('attendance_imports', ['id' => $batch->id]);
    $this->assertDatabaseMissing('attendance_punches', ['attendance_import_id' => $batch->id]);
    $this->assertDatabaseHas('attendance_punches', ['biometric_id' => 16, 'punched_at' => '2026-10-20 08:00:00']);
    $this->assertDatabaseHas('attendance_records', ['id' => $manual->id]);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'attendance.logs_removed', 'model_id' => $batch->id]);
    expect($employee->refresh()->biometric_status)->toBe('pending')
        ->and($employee->biometric_verified_at)->toBeNull()
        ->and($employee->biometric_locked)->toBeTrue()
        ->and($otherEmployee->refresh()->biometric_status)->toBe('verified');

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('first.dat', $file)])->assertSessionHasNoErrors();
    expect($employee->refresh()->biometric_status)->toBe('verified');
});

test('saving or correcting an employee biometric ID links historical and future punches without rewriting them', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $employee = Employee::factory()->create(['date_hired' => '2020-01-01']);
    $other = Employee::factory()->create();
    $url = '/hris/attendance/logs';
    $linkUrl = "/hris/employees/{$employee->id}/biometric-id";
    $lockUrl = $linkUrl.'/lock';
    $dtr = "/hris/attendance/dtr/{$employee->id}";
    $file = " 15\t2026-10-19 17:10:00\n15\t2026-10-19 12:03:00\n15\t2026-10-19 07:56:00\n15\t2026-10-19 12:58:00\n";

    $this->actingAs($hr)->post($url, ['file' => UploadedFile::fake()->createWithContent('first.dat', $file)])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect(AttendancePunch::query()->unassigned()->count())->toBe(4);
    $this->get('/hris/employees')->assertInertia(fn (Assert $page) => $page
        ->where('canLinkBiometricIds', true)
        ->where('records.data.0.biometric_id', null)
        ->where('records.data.0.biometric_locked', false));

    $this->from('/hris/employees')->patch($lockUrl, ['locked' => true])->assertSessionHasErrors('locked');
    $this->from('/hris/employees')->put($linkUrl, ['biometric_id' => '00015'])->assertRedirect('/hris/employees');
    expect($employee->refresh()->biometric_id)->toBe(15)
        ->and($employee->biometric_status)->toBe('verified')
        ->and($employee->biometric_verified_by)->toBe($hr->id)
        ->and($employee->biometric_note)->toContain('Only 4')
        ->and($employee->attendancePunches()->count())->toBe(4)
        ->and(AttendancePunch::query()->unassigned()->count())->toBe(0);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'employee.biometric_link_updated', 'model_id' => $employee->id]);
    $this->getJson($dtr)->assertOk()
        ->assertJsonPath('month', '2026-10')
        ->assertJsonPath('records.19', ['amIn' => '07:56', 'amOut' => '12:03', 'pmIn' => '12:58', 'pmOut' => '17:10']);

    $this->from('/hris/employees')->patch($lockUrl, ['locked' => true])
        ->assertRedirect('/hris/employees')->assertSessionHasNoErrors();
    expect($employee->refresh()->biometric_locked)->toBeTrue();
    foreach ([16, null] as $id) {
        $this->from('/hris/employees')->put($linkUrl, ['biometric_id' => $id])->assertSessionHasErrors('biometric_id');
    }
    expect($employee->refresh()->biometric_id)->toBe(15);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'employee.biometric_link_locked', 'model_id' => $employee->id]);

    $this->from('/hris/employees')->put("/hris/employees/{$other->id}/biometric-id", ['biometric_id' => 15])
        ->assertSessionHasErrors('biometric_id');
    expect($other->refresh()->biometric_id)->toBeNull();

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('future.dat', "15\t2026-10-20 08:00:00\n")])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($employee->refresh()->biometric_status)->toBe('verified')
        ->and($employee->biometric_locked)->toBeTrue()
        ->and($employee->biometric_verified_by)->toBe($hr->id)
        ->and($employee->biometric_note)->toContain('Only 4')
        ->and($employee->attendancePunches()->count())->toBe(5);
    $this->getJson($dtr)->assertJsonPath('records.20.remarks', 'Review 1 punch(es)')
        ->assertJsonPath('punchTimes.20', ['08:00:00']);

    $this->from('/hris/employees')->patch($lockUrl, ['locked' => false])
        ->assertRedirect('/hris/employees')->assertSessionHasNoErrors();
    expect($employee->refresh()->biometric_locked)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'employee.biometric_link_unlocked', 'model_id' => $employee->id]);
    $this->from('/hris/employees')->put($linkUrl, ['biometric_id' => ''])->assertRedirect('/hris/employees');
    expect($employee->refresh()->biometric_id)->toBeNull()
        ->and(AttendancePunch::query()->unassigned()->count())->toBe(5);
    $this->from('/hris/employees')->put("/hris/employees/{$other->id}/biometric-id", ['biometric_id' => 15])->assertRedirect('/hris/employees');
    expect($other->refresh()->attendancePunches()->count())->toBe(5);
    $this->assertDatabaseCount('attendance_punches', 5);
});

test('verification reports missing punches and dates before hire', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    $early = Employee::factory()->create(['date_hired' => '2026-10-19']);
    $missing = Employee::factory()->create();
    $valid = Employee::factory()->create(['date_hired' => '2026-10-19']);
    $this->actingAs($hr)->from('/hris/employees')
        ->put("/hris/employees/{$early->id}/biometric-id", ['biometric_id' => 15])->assertRedirect();
    $this->put("/hris/employees/{$missing->id}/biometric-id", ['biometric_id' => 30])->assertRedirect();
    $this->put("/hris/employees/{$valid->id}/biometric-id", ['biometric_id' => 31])->assertRedirect();
    expect($valid->refresh()->biometric_status)->toBe('pending');
    $this->post('/hris/attendance/logs', ['file' => UploadedFile::fake()->createWithContent('check.dat', "15\t2026-10-18 08:00:00\n31\t2026-10-19 08:00:00\n")])
        ->assertSessionHasNoErrors()->assertRedirect();

    expect($early->refresh()->biometric_status)->toBe('pending')
        ->and($early->biometric_note)->toContain('before')
        ->and($missing->refresh()->biometric_status)->toBe('pending')
        ->and($missing->biometric_note)->toContain('No imported punches')
        ->and($valid->refresh()->biometric_status)->toBe('verified');
});

test('pending biometric links can be verified without changing a locked or stale ID', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $this->actingAs($hr)->post('/hris/attendance/logs', [
        'file' => UploadedFile::fake()->createWithContent('existing.dat', "15\t2026-10-19 07:56:00\n"),
    ])->assertSessionHasNoErrors();
    $employee = Employee::factory()->create(['date_hired' => '2020-01-01']);
    $employee->forceFill(['biometric_id' => 15, 'biometric_status' => 'pending'])->save();
    $url = "/hris/employees/{$employee->id}/biometric-id";

    $this->from('/hris/employees')->put($url, ['biometric_id' => 16, 'verify_only' => true])->assertSessionHasErrors('biometric_id');
    expect($employee->refresh()->biometric_id)->toBe(15);
    $this->patch($url.'/lock', ['locked' => true])->assertSessionHasNoErrors();
    $this->put($url, ['biometric_id' => 15, 'verify_only' => true])->assertSessionHasNoErrors();
    expect($employee->refresh()->biometric_status)->toBe('verified')
        ->and($employee->biometric_locked)->toBeTrue()
        ->and($employee->biometric_id)->toBe(15);
});

test('department heads cannot import logs or view or edit biometric IDs', function () {
    $employee = Employee::factory()->create();
    $head = User::factory()->for(Role::factory()->state(['name' => 'Department Head']))->create(['employee_id' => $employee->id]);
    $this->actingAs($head)->get('/hris/attendance/logs')->assertForbidden();
    $this->post('/hris/attendance/logs')->assertForbidden();
    $this->put("/hris/employees/{$employee->id}/biometric-id", ['biometric_id' => 1])->assertForbidden();
    $this->patch("/hris/employees/{$employee->id}/biometric-id/lock", ['locked' => false])->assertForbidden();
    $this->get('/hris/employees')->assertInertia(fn (Assert $page) => $page
        ->where('canLinkBiometricIds', false)
        ->missing('records.data.0.biometric_id'));
});

test('an export with nine thousand punches is written in chunks', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $file = '';
    for ($i = 0; $i < 9000; $i++) {
        $file .= sprintf(" 15\t2026-10-19 %02d:%02d:%02d\t1\t2\t1\t0\r\n", intdiv($i, 3600), intdiv($i % 3600, 60), $i % 60);
    }
    $this->actingAs($hr)->post('/hris/attendance/logs', ['file' => UploadedFile::fake()->createWithContent('large.dat', $file)])
        ->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('attendance_imports', ['inserted_count' => 9000, 'duplicate_count' => 0]);
    $this->assertDatabaseCount('attendance_punches', 9000);
});
