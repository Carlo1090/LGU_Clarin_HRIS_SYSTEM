<?php

use App\Models\AttendanceClockInRule;
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

test('the monthly report groups linked punches by date and preserves unknown lateness when sorting', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $adams = Employee::factory()->create(['last_name' => 'Adams', 'date_hired' => '2020-01-01']);
    $brown = Employee::factory()->create(['last_name' => 'Brown', 'date_hired' => '2020-01-01']);
    $zulu = Employee::factory()->create(['last_name' => 'Zulu', 'date_hired' => '2020-01-01']);
    $withoutPunches = Employee::factory()->create(['last_name' => 'No punches', 'date_hired' => '2020-01-01']);
    AttendanceRecord::factory()->for($withoutPunches)->create(['attendance_date' => '2026-10-19']);
    $url = '/hris/attendance/monthly-report';
    $logs = '/hris/attendance/logs';
    $file = '';
    foreach ([
        [15, '2026-10-20', '08:05:01'],
        [16, '2026-10-19', '08:01:00'],
        [15, '2026-10-19', '08:05:00'],
        [16, '2026-10-21', '08:01:00'],
        [17, '2026-10-21', '08:01:00'],
    ] as [$id, $date, $start]) {
        foreach ([
            ['17:00:00', 1],
            [$start, 0],
            ['12:01:00', 2],
            ['13:00:00', 3],
        ] as [$time, $state]) {
            $file .= "{$id}\t{$date} {$time}\t1\t{$state}\t1\t0\n";
        }
    }
    $file .= "15\t2026-10-21 17:00:00\t1\t1\t1\t0\n99\t2026-10-21 08:00:00\t1\t0\t1\t0\n98\t2026-11-01 08:00:00\t1\t0\t1\t0\n";
    $this->actingAs($hr)->from($logs)->post($logs, [
        'file' => UploadedFile::fake()->createWithContent('monthly.dat', $file),
        'confirm' => true,
    ])->assertSessionHasNoErrors();

    foreach ([[$adams, 15, 5], [$brown, 16, 0], [$zulu, 17, null]] as [$employee, $biometricId, $grace]) {
        $this->from('/hris/employees')->put("/hris/employees/{$employee->id}/biometric-id", ['biometric_id' => $biometricId])
            ->assertSessionHasNoErrors();
        if ($grace !== null) {
            $this->from($url)->put("{$url}/{$employee->id}/clock-in-rule", [
                'effective_from' => '2026-10-19', 'starts_at' => '08:00', 'grace_minutes' => $grace,
            ])->assertSessionHasNoErrors();
        }
    }

    $this->get($url."?month=2026-10&employee={$adams->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('hris/monthly-attendance-report')
        ->where('unlinkedIds', 1)
        ->where('summary.data.0.id', $adams->id)
        ->where('summary.data.0.employee_number', $adams->employee_number)
        ->where('summary.data.0.days_attended', 3)
        ->where('summary.data.0.late_arrivals', 1)
        ->where('summary.data.0.undetermined_days', 1)
        ->where('summary.data.3.days_attended', 0)
        ->has('detail.days', 3)
        ->where('detail.days.0.work_date', '2026-10-19')
        ->where('detail.days.0.clock_in', '08:05:00')
        ->where('detail.days.0.lateness', 'on_time')
        ->where('detail.days.1.clock_in', '08:05:01')
        ->where('detail.days.1.lateness', 'late')
        ->where('detail.days.2.clock_in', null)
        ->where('detail.days.2.lateness', 'undetermined'));

    foreach ([
        ['days_attended', 'asc', [$withoutPunches, $zulu, $brown, $adams]],
        ['days_attended', 'desc', [$adams, $brown, $zulu, $withoutPunches]],
        ['late_arrivals', 'asc', [$withoutPunches, $adams, $brown, $zulu]],
        ['late_arrivals', 'desc', [$brown, $adams, $withoutPunches, $zulu]],
    ] as [$sort, $direction, $order]) {
        $this->get($url."?month=2026-10&sort={$sort}&direction={$direction}")->assertInertia(fn (Assert $page) => $page
            ->where('summary.data.0.id', $order[0]->id)
            ->where('summary.data.1.id', $order[1]->id)
            ->where('summary.data.2.id', $order[2]->id)
            ->where('summary.data.3.id', $order[3]->id));
    }
    $this->get($logs.'?month=2026-10&unassigned=1&biometric_id=99')->assertInertia(fn (Assert $page) => $page
        ->has('groups.data', 1)->where('groups.data.0.biometric_id', 99)
        ->where('punches.data.0.punched_at', '2026-10-21 08:00:00'));

    $this->from('/hris/employees')->put("/hris/employees/{$withoutPunches->id}/biometric-id", ['biometric_id' => 99])
        ->assertSessionHasNoErrors();
    $this->from($logs)->post($logs, [
        'file' => UploadedFile::fake()->createWithContent('additional.dat', "99\t2026-10-22 08:00:00\t1\t0\t1\t0\n"),
        'confirm' => true,
    ])->assertSessionHasNoErrors();
    $this->get($url."?month=2026-10&employee={$withoutPunches->id}")->assertInertia(fn (Assert $page) => $page
        ->where('unlinkedIds', 0)->has('detail.days', 2));
});

test('clock-in rules are effective-dated, reversible and restricted to attendance managers', function () {
    $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $employee = Employee::factory()->create(['date_hired' => '2020-01-01']);
    $employee->forceFill(['biometric_id' => 15])->save();
    $url = '/hris/attendance/monthly-report';
    $rule = "{$url}/{$employee->id}/clock-in-rule";
    $file = '';
    foreach (['19', '20', '21', '22'] as $day) {
        foreach (['08:10:00', '12:00:00', '13:00:00', '17:00:00'] as $time) {
            $state = match ($time) {
                '08:10:00' => 0,
                '12:00:00' => 2,
                '13:00:00' => 3,
                default => 1,
            };
            $file .= "15\t2026-10-{$day} {$time}\t1\t{$state}\t1\t0\n";
        }
    }
    $this->actingAs($hr)->post('/hris/attendance/logs', [
        'file' => UploadedFile::fake()->createWithContent('rule-dates.dat', $file),
        'confirm' => true,
    ])->assertSessionHasNoErrors();
    $this->from($url)->put($rule, ['effective_from' => '2026-10-20', 'starts_at' => '08:00', 'grace_minutes' => 0])->assertSessionHasNoErrors();
    $this->put($rule, ['effective_from' => '2026-10-21', 'starts_at' => '08:00', 'grace_minutes' => 10])->assertSessionHasNoErrors();
    $this->put($rule, ['effective_from' => '2026-10-22', 'starts_at' => null, 'grace_minutes' => 10])->assertSessionHasNoErrors();
    $this->put($rule, ['effective_from' => '2026-10-22', 'starts_at' => '25:00', 'grace_minutes' => -1])
        ->assertSessionHasErrors(['starts_at', 'grace_minutes']);

    $this->get($url."?month=2026-10&employee={$employee->id}")->assertInertia(fn (Assert $page) => $page
        ->where('summary.data.0.days_attended', 4)
        ->where('summary.data.0.late_arrivals', 1)
        ->where('summary.data.0.undetermined_days', 2)
        ->where('detail.days.0.lateness', 'undetermined')
        ->where('detail.days.1.lateness', 'late')
        ->where('detail.days.2.lateness', 'on_time')
        ->where('detail.days.3.lateness', 'undetermined')
        ->where('detail.rule.starts_at', null));
    expect(AttendanceClockInRule::query()->count())->toBe(3);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $hr->id, 'action' => 'attendance.clock_in_rule_saved']);

    $head = User::factory()->for(Role::factory()->state(['name' => 'Department Head']))->create();
    $this->actingAs($head)->get($url)->assertForbidden();
    $this->put($rule, ['effective_from' => '2026-10-22', 'starts_at' => '08:00', 'grace_minutes' => 0])->assertForbidden();
});
