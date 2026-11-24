<?php

use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('daily punches show all slots, wrong buttons, duplicate terminals, pin review, and unlinked machines', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $department = Department::factory()->create();
    foreach ([15, 16, 17, 18] as $id) {
        Employee::factory()->create([
            'department_id' => $department->id,
            'biometric_id' => $id,
            'date_hired' => '2020-01-01',
        ]);
    }

    $file = implode("\r\n", [
        "15\t2026-10-19 08:00:00\t1\t0\t1\t0",
        "15\t2026-10-19 08:00:01\t2\t0\t1\t0",
        "15\t2026-10-19 12:00:00\t1\t2\t1\t0",
        "15\t2026-10-19 13:00:00\t1\t3\t1\t0",
        "15\t2026-10-19 17:00:00\t1\t1\t1\t0",
        "16\t2026-10-19 08:00:00\t1\t0\t1\t0",
        "16\t2026-10-19 12:00:00\t1\t2\t1\t0",
        "16\t2026-10-19 17:00:00\t1\t1\t1\t0",
        "17\t2026-10-19 08:00:00\t1\t0\t1\t0",
        "17\t2026-10-19 12:00:00\t1\t2\t1\t0",
        "17\t2026-10-19 12:50:00\t1\t0\t0\t0",
        "17\t2026-10-19 17:00:00\t1\t1\t1\t0",
        "18\t2026-10-19 08:00:00\t1\t0\t1\t0",
        "18\t2026-10-19 08:01:00\t1\t0\t1\t0",
        "99\t2026-10-19 08:00:00\t1\t0\t1\t0",
    ])."\r\n";

    $url = '/hris/attendance/logs';
    $this->actingAs($hr)->post($url, [
        'file' => UploadedFile::fake()->createWithContent('daily.dat', $file),
        'confirm' => true,
    ])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseCount('attendance_punches', 15);
    $this->assertDatabaseHas('attendance_punches', [
        'machine_user_id' => 15,
        'punched_at' => '2026-10-19 08:00:01',
        'device_id' => 2,
    ]);

    $this->get('/hris/attendance/daily-punches?start_date=2026-10-19&end_date=2026-10-19')
        ->assertOk()->assertInertia(fn ($page) => $page
        ->component('hris/daily-punches')
        ->has('rows.data', 5)
        ->where('rows.data.0.employee_name', fn ($name) => is_string($name))
        ->where('rows.data.0.slots.AM_IN.0.time', '08:00:00')
        ->where('rows.data.0.slots.AM_IN.1.time', '08:00:01')
        ->where('rows.data.1.flags.missing_slots', ['PM_IN'])
        ->where('rows.data.1.slots.PM_IN', [])
        ->where('rows.data.2.flags.wrong_button', true)
        ->where('rows.data.2.flags.missing', false)
        ->where('rows.data.2.punches.2.flags.suggested_slot', 'PM_IN')
        ->where('rows.data.2.flags.pin_verify', true)
        ->where('rows.data.3.punches.1.is_duplicate', true)
        ->where('rows.data.4.flags.unlinked', true)
        ->where('rows.data.4.employee_name', null));

    $this->get('/hris/attendance/daily-punches?start_date=2026-10-19&end_date=2026-10-19&flags_only=wrong_button')
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.machine_user_id', 17));

    $export = $this->get('/hris/attendance/daily-punches/export?start_date=2026-10-19&end_date=2026-10-19')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
    expect($export->streamedContent())
        ->toContain('ss:Name="Daily Punches"')
        ->toContain('ss:Name="Raw Punches"')
        ->toContain('2026-10-19 12:50:00');

    $this->post($url, [
        'file' => UploadedFile::fake()->createWithContent('re-upload.dat', $file),
        'confirm' => true,
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect(AttendanceImport::query()->count())->toBe(2)
        ->and(AttendanceImport::query()->latest('id')->value('inserted_count'))->toBe(0)
        ->and(AttendanceImport::query()->latest('id')->value('duplicate_count'))->toBe(15)
        ->and(AttendancePunch::query()->count())->toBe(15);
});
