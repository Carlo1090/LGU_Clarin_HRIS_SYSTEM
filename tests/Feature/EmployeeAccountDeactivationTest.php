<?php

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeAccountDeactivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Laravel\Passkeys\Models\Passkey;
use Laravel\Passkeys\Passkeys;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('HRMO Head deactivation requires a password and reason and revokes employee sessions and codes', function () {
    config()->set('session.driver', 'database');
    $this->withoutVite();
    $head = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    $record = Employee::factory()->create();
    $employee = User::factory()->for(Role::factory()->state(['name' => 'Employee']))->for($record)->create();
    $record->forceFill(['user_id' => $employee->id])->save();
    $code = $employee->activationCodes()->create(['code_hash' => 'fixture-hash', 'expires_at' => now()->addDay()]);
    DB::table('sessions')->insert([
        'id' => 'employee-session', 'user_id' => $employee->id, 'payload' => 'fixture', 'last_activity' => now()->timestamp,
    ]);
    $service = app(EmployeeAccountDeactivation::class);

    expect(fn () => $service->deactivate($head, $employee, 'wrong', 'Reason'))->toThrow(ValidationException::class)
        ->and(fn () => $service->deactivate($head, $employee, 'password', ' '))->toThrow(ValidationException::class)
        ->and($employee->fresh()->is_active)->toBeTrue();

    $this->actingAs($head)->post(route('hris.employees.deactivate', $record), [
        'password' => 'password', 'reason' => 'Requested by personnel office.',
    ])->assertRedirect(route('hris.profile-review.index'));

    $entry = AuditLog::query()->where('action', 'employee.account_deactivated')->sole();
    expect($employee->fresh()->is_active)->toBeFalse()
        ->and($code->fresh()->revoked_at)->not->toBeNull()
        ->and($entry->user_id)->toBe($head->id)
        ->and($entry->model_id)->toBe($employee->id)
        ->and(json_decode($entry->getRawOriginal('metadata'), true))->toMatchArray([
            'reason' => 'Requested by personnel office.', 'sessions_revoked' => 1, 'fallback' => false,
        ]);
    $this->assertDatabaseMissing('sessions', ['id' => 'employee-session']);
    $this->get(route('hris.profile-review.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('audit.data.0.reactivateEmployeeId', $record->id)
        ->where('audit.data.0.details.sessions_revoked', 1));
    $passkey = (new Passkey)->setRelation('user', $employee->fresh());
    expect(Passkeys::allowsLogin(request(), $passkey))->toBeFalse();

    $slip = $this->post(route('hris.employees.reactivate', $record), ['password' => 'password'], ['X-Inertia' => 'true'])
        ->assertOk()->json('flash.activationSlips.0');
    $freshCode = $employee->activationCodes()->whereNull('revoked_at')->sole();
    expect($employee->fresh()->is_active)->toBeTrue()
        ->and($employee->fresh()->password)->toBeNull()
        ->and(Hash::check(str_replace('-', '', $slip['activation_code']), $freshCode->code_hash))->toBeTrue();
    expect(Passkeys::allowsLogin(request(), $passkey->setRelation('user', $employee->fresh())))->toBeFalse();
});

test('HR Officers cannot deactivate employees and HRMO Heads cannot deactivate HR Officers', function () {
    $head = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    $officer = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    $employee = User::factory()->for(Role::factory()->state(['name' => 'Employee']))->create();
    $service = app(EmployeeAccountDeactivation::class);

    expect(fn () => $service->deactivate($officer, $employee, 'password', 'Reason'))->toThrow(HttpException::class)
        ->and(fn () => $service->deactivate($head, $officer, 'password', 'Reason'))->toThrow(HttpException::class)
        ->and($employee->fresh()->is_active)->toBeTrue()
        ->and($officer->fresh()->is_active)->toBeTrue();
});
