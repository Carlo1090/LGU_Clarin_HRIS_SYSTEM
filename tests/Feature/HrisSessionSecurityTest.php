<?php

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('HRIS accounts record last login and expire after their configured idle time', function (string $role, int $minutes) {
    $this->withoutVite();
    $this->freezeSecond();
    $user = User::factory()->for(Role::factory()->state(['name' => $role]))->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => true])->assertRedirect();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('login_activities', ['user_id' => $user->id, 'status' => 'success', 'session_id' => null]);
    $this->get(route('profile.edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('showLastLogin', true)->where('lastLoginAt', now()->toIso8601String()));
    $this->travel($minutes - 1)->minutes();
    $this->get(route('profile.edit'))->assertOk();
    $this->travel($minutes - 1)->minutes();
    $this->get(route('dashboard'), ['X-HRIS-Background-Poll' => '1'])->assertOk();
    $this->travel(1)->minute();
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->assertGuest();
})->with([
    ['HRMO Head', 15],
    ['HR Officer', 15],
    ['Employee', 30],
]);

test('employee sign-in locks across employee number and email and HR can see the expiry', function () {
    $this->withoutVite();
    $this->freezeSecond();
    $employee = Employee::factory()->create();
    $user = User::factory()->for($employee)->for(Role::factory()->state(['name' => 'Employee']))->create();
    $head = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
    for ($failure = 0; $failure < 5; $failure++) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }
    $this->post(route('login.store'), ['email' => $employee->employee_number, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->actingAs($head)->get(route('dashboard'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('employeeLockouts.0.employeeNumber', $employee->employee_number)
        ->where('employeeLockouts.0.kind', 'Sign-in')
        ->where('employeeLockouts.0.expiresAt', now()->addMinutes(15)->toIso8601String()));
    $this->post(route('logout'));
    $this->travel(15)->minutes();
    $this->post(route('login.store'), ['email' => $employee->employee_number, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('new security enrollment is off for scoped roles while existing TOTP management stays available', function (string $role) {
    $this->withoutVite();
    $user = User::factory()->unverified()->for(Role::factory()->state(['name' => $role]))->create(['requires_mfa' => true]);
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => now()->timestamp]);

    $this->get(route('security.edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canManageTwoFactor', false)->where('canEnrollPasskeys', false)->where('requiresMfaEnrollment', false));
    $this->post(route('two-factor.enable'))->assertForbidden();
    $this->get(route('passkey.registration-options'))->assertForbidden();
    $this->post(route('verification.send'))->assertForbidden();

    $user->forceFill(['two_factor_secret' => encrypt('test-secret'), 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($user->refresh())->get(route('security.edit'))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManageTwoFactor', true)->where('twoFactorEnabled', true));
})->with(['HRMO Head', 'HR Officer', 'Employee']);
