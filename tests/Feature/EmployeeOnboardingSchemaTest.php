<?php

use App\Attendance\Models\BiometricLink;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('activation codes keep the issuer and hide the stored hash from serialization', function () {
    $employeeAccount = User::factory()->create();
    $issuer = User::factory()->create();

    $code = $employeeAccount->activationCodes()->create([
        'issued_by' => $issuer->id,
        'code_hash' => Hash::make('test-code'),
        'expires_at' => now()->addDay(),
    ])->refresh();

    expect((int) $code->issued_by)->toBe($issuer->id)
        ->and(Hash::check('test-code', $code->code_hash))->toBeTrue()
        ->and($code->toArray())->not->toHaveKey('code_hash')
        ->and($code->used_at)->toBeNull()
        ->and($code->revoked_at)->toBeNull();
});

test('biometric matching uses effective boundaries independently of audit timestamps', function () {
    $this->travelTo(Carbon::parse('2026-11-24 08:00:00'));
    $recorder = User::factory()->create();
    $former = Employee::factory()->create();
    $current = Employee::factory()->create();
    $boundary = '2026-11-10 07:00:00';

    $former->biometricLinks()->create([
        'biometric_id' => 51,
        'effective_to' => $boundary,
        'recorded_by' => $recorder->id,
    ]);
    $link = $current->biometricLinks()->create([
        'biometric_id' => 51,
        'effective_from' => $boundary,
        'recorded_by' => $recorder->id,
    ])->refresh();

    $employeeAt = fn (string $punch) => BiometricLink::query()
        ->where('biometric_id', 51)
        ->effectiveAt(Carbon::parse($punch))
        ->sole()
        ->employee_id;

    expect($employeeAt('2026-11-10 06:59:59'))->toBe($former->id)
        ->and($employeeAt($boundary))->toBe($current->id)
        ->and($employeeAt('2026-11-25 08:00:00'))->toBe($current->id)
        ->and($link->created_at->toDateTimeString())->toBe('2026-11-24 08:00:00')
        ->and((int) $link->recorded_by)->toBe($recorder->id);
});

test('department history can be recorded without replacing the current office', function () {
    $oldOffice = Department::factory()->create();
    $newOffice = Department::factory()->create();
    $employee = Employee::factory()->for($newOffice)->create();
    $recorder = User::factory()->create();

    $assignment = $employee->departmentAssignments()->create([
        'department_id' => $oldOffice->id,
        'effective_from' => '2026-07-01',
        'effective_to' => '2026-11-30',
        'recorded_by' => $recorder->id,
    ])->refresh();

    expect((int) $assignment->department_id)->toBe($oldOffice->id)
        ->and((int) $assignment->recorded_by)->toBe($recorder->id)
        ->and($assignment->effective_to->toDateString())->toBe('2026-11-30')
        ->and((int) $employee->refresh()->department_id)->toBe($newOffice->id);
});
