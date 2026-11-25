<?php

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('identifier encryption can be counted, resumed, and reversed', function () {
    $employee = Employee::factory()->create();
    DB::table('employees')->where('id', $employee->id)->update(['tin' => '001234567', 'gsis_bp_number' => '000098765']);
    Employee::factory()->create(['tin' => null, 'gsis_bp_number' => null]);
    $row = fn () => DB::table('employees')->where('id', $employee->id)->first();

    $this->artisan('employees:encrypt-identifiers')->expectsOutput('Dry run: 2 employee rows scanned; 1 affected; 2 identifier values.')->assertSuccessful();
    expect($row()->tin)->toBe('001234567');
    $this->artisan('employees:encrypt-identifiers', ['--apply' => true])->assertFailed();
    $this->artisan('employees:encrypt-identifiers', ['--apply' => true, '--backup-verified' => true])->assertSuccessful();
    $ciphertext = $row()->tin;
    expect($ciphertext)->toStartWith('hris-encrypted:v1:')->not->toContain('001234567');
    $this->artisan('employees:encrypt-identifiers', ['--apply' => true, '--backup-verified' => true])
        ->expectsOutput('Applied: 2 employee rows scanned; 0 affected; 0 identifier values.')->assertSuccessful();
    expect($row()->tin)->toBe($ciphertext);
    $migration = require database_path('migrations/2026_11_25_000003_widen_employee_identifiers_for_encryption.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Restore identifiers to their original format');
    $this->artisan('employees:encrypt-identifiers', ['--apply' => true, '--reverse' => true, '--backup-verified' => true])->assertSuccessful();
    expect($row()->tin)->toBe('001234567')->and($row()->gsis_bp_number)->toBe('000098765');
});

test('full identifiers require password re-entry and authorized ownership', function () {
    $employee = Employee::factory()->create(['tin' => '001234567']);
    $owner = User::factory()->for($employee)->for(Role::factory()->state(['name' => 'Employee']))->create();
    $officer = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $head = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    $url = route('hris.employee.identifier.reveal', [$employee, 'tin']);

    $this->actingAs($officer)->postJson($url, ['password' => 'password'])->assertForbidden();
    $this->actingAs($owner)->postJson($url, ['password' => 'wrong'])->assertUnprocessable();
    $this->postJson($url, ['password' => 'password'])->assertOk()->assertJsonPath('value', '001234567')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs($head)->postJson($url, ['password' => 'password'])->assertOk()->assertJsonPath('value', '001234567');

    $reveals = DB::table('audit_logs')->where('action', 'employee.identifier_revealed')->orderBy('id')->get();
    expect($reveals->pluck('user_id')->all())->toBe([$owner->id, $head->id])
        ->and($reveals->pluck('metadata')->implode(' '))->not->toContain('001234567');
});

test('employee identifiers encrypt on write and stay hidden in serialized employee records', function () {
    $employee = Employee::factory()->create(['tin' => '001234567', 'gsis_bp_number' => '1234']);
    $fresh = $employee->fresh();
    expect($fresh->tin)->toBe('001234567')
        ->and($fresh->gsis_bp_number)->toBe('1234')
        ->and($fresh->getRawOriginal('tin'))->toStartWith('hris-encrypted:v1:')
        ->and($fresh->toArray())->not->toHaveKey('tin')
        ->and($fresh->toArray())->not->toHaveKey('gsis_bp_number')
        ->and(\App\Casts\EmployeeIdentifier::mask($fresh->gsis_bp_number))->toBe('••••');
});
