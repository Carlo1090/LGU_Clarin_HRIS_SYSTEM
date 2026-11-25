<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeProfileDraft;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('session.driver', 'database');
    $this->withoutVite();
});

test('employee activates once and opens their profile without verified email', function () {
    $employee = Employee::factory()->create(['tin' => '123456789', 'gsis_bp_number' => '987654321']);
    $user = User::factory()->unverified()->for($employee)->for(Role::factory()->state(['name' => 'Employee']))
        ->create(['password' => null, 'requires_mfa' => true]);
    $code = $user->activationCodes()->create(['code_hash' => Hash::make('KM24R992'), 'expires_at' => now()->addDay()]);
    $input = [
        'employee_number' => $employee->employee_number,
        'activation_code' => 'km24 - r992',
        'password' => 'unrelated local passphrase',
        'password_confirmation' => 'unrelated local passphrase',
    ];

    $this->post(route('employee.activate.store'), $input)->assertRedirect(route('hris.my-profile'));
    $this->assertAuthenticatedAs($user);
    expect($code->fresh()->used_at)->not->toBeNull();
    $this->get(route('hris.my-profile'))->assertOk()->assertInertia(fn ($page) => $page
        ->where('employee.tin', '••••6789')->where('employee.gsis_bp_number', '••••4321'));
    $this->get(route('dashboard'))->assertRedirect(route('hris.my-profile'));
    $this->patchJson(route('hris.my-profile.draft'), [
        'tin' => '999111222', 'pwd' => 'yes', 'pwd_id_number' => 'PWD-123',
        'first_name' => 'Must not be written',
    ])->assertNoContent();
    $draft = EmployeeProfileDraft::query()->findOrFail($employee->id);
    expect($draft->values['tin'])->toBe('999111222')
        ->and($draft->values)->not->toHaveKey('first_name')
        ->and(DB::table('employee_profile_drafts')->where('employee_id', $employee->id)->value('values'))->not->toContain('999111222')
        ->and($employee->fresh()->tin)->toBe('123456789');
    $this->get(route('hris.my-profile'))->assertInertia(fn ($page) => $page
        ->missing('draft.tin')->missing('draft.pwd_id_number')->where('draft.pwd', 'yes'));
    $this->patchJson(route('hris.my-profile.draft'), ['pwd' => 'no'])->assertNoContent();
    expect($draft->fresh()->values)->not->toHaveKey('pwd_id_number');
    $this->post(route('logout'));
    $this->post(route('employee.activate.store'), $input)->assertSessionHasErrors('activation_code');
    $this->assertGuest();
});

test('failed activation attempts lock temporarily and cancel the code only after 25 failures', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->for($employee)->for(Role::factory()->state(['name' => 'Employee']))->create(['password' => null]);
    $code = $user->activationCodes()->create(['code_hash' => Hash::make('KM24R992'), 'expires_at' => now()->addDay()]);
    $input = [
        'employee_number' => $employee->employee_number, 'activation_code' => 'invalid',
        'password' => 'unrelated local passphrase', 'password_confirmation' => 'unrelated local passphrase',
    ];

    for ($window = 0; $window < 5; $window++) {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('employee.activate.store'), $input)->assertSessionHasErrors('activation_code');
        }
        if ($window < 4) {
            $this->post(route('employee.activate.store'), array_replace($input, ['activation_code' => 'KM24R992']))
                ->assertSessionHasErrors('activation_code');
            expect($code->fresh()->revoked_at)->toBeNull();
            $this->travel(16)->minutes();
        }
    }

    expect($code->fresh()->revoked_at)->not->toBeNull()
        ->and($code->fresh()->used_at)->toBeNull()
        ->and($user->fresh()->is_active)->toBeTrue();
});

test('recovery requires HR password and revokes employee sessions at redemption', function () {
    $employee = Employee::factory()->for(Department::factory())->create();
    $user = User::factory()->for($employee)->for(Role::factory()->state(['name' => 'Employee']))->create();
    $employee->forceFill(['user_id' => $user->id])->save();
    $officer = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
    DB::table('sessions')->insert([
        'id' => 'before-recovery', 'user_id' => $user->id, 'payload' => 'fixture', 'last_activity' => now()->timestamp,
    ]);
    $issue = route('hris.employees.activation.reissue', $employee);
    $this->actingAs($officer)->post($issue, ['password' => 'wrong'])->assertSessionHasErrors('password');
    $this->post($issue, ['password' => 'password', 'revoke_now' => true])->assertForbidden();
    $slip = $this->post($issue, ['password' => 'password'], ['X-Inertia' => 'true'])
        ->assertOk()->json('flash.activationSlips.0');
    $this->assertDatabaseHas('sessions', ['id' => 'before-recovery']);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();

    $this->post(route('logout'));
    $this->post(route('employee.activate.store'), [
        'employee_number' => $employee->employee_number,
        'activation_code' => $slip['activation_code'],
        'password' => 'replacement passphrase here',
        'password_confirmation' => 'replacement passphrase here',
    ])->assertRedirect(route('hris.my-profile'));
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseMissing('sessions', ['id' => 'before-recovery']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'employee.activation_recovery_used', 'user_id' => $user->id]);

    DB::table('sessions')->insert([
        'id' => 'compromised-session', 'user_id' => $user->id, 'payload' => 'fixture', 'last_activity' => now()->timestamp,
    ]);
    $head = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    $this->actingAs($head)->post($issue, ['password' => 'password', 'revoke_now' => true], ['X-Inertia' => 'true'])
        ->assertOk()->assertJsonStructure(['flash' => ['activationSlips' => [['activation_code']]]]);
    $this->assertDatabaseMissing('sessions', ['id' => 'compromised-session']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'employee.sessions_revoked', 'user_id' => $head->id, 'model_id' => $user->id]);
});
