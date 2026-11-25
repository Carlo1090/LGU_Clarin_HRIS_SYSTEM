<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Notifications\HrisAccountSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioned_hrmo_head_invites_an_employee_and_enrollment_is_required(): void
    {
        $this->withoutVite();
        $this->seed();
        Notification::fake();

        $actor = User::factory()->create([
            'role_id' => Role::query()->where('name', 'HRMO Head')->value('id'),
            'requires_mfa' => true,
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $employee = Employee::factory()->for(Department::query()->where('code', 'HEALTH')->firstOrFail())->create();
        $input = [
            'employee_number' => $employee->employee_number,
            'email' => 'employee@example.test',
            'role' => 'Department Head',
            'approval_reference' => 'TEST-APPROVAL',
        ];

        $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('hris.invitations.store'), $input)->assertForbidden();

        $this->actingAs($actor)->get(route('hris.invitations.create'))
            ->assertOk()->assertInertia(fn ($page) => $page->component('hris/invite-account')->has('roles', 9));
        $this->post(route('hris.invitations.store'), array_replace($input, ['role' => 'HRMO Head']))
            ->assertSessionHasErrors('role');
        $this->post(route('hris.invitations.store'), $input)->assertSessionHasNoErrors()->assertRedirect();

        $account = User::query()->where('email', $input['email'])->firstOrFail();
        Notification::assertSentTo($account, HrisAccountSetup::class);
        $this->assertSame($account->id, $employee->refresh()->user_id);
        $this->assertSame('Department Head', $account->role->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.invitation_created', 'model_id' => $account->id]);
        $token = Notification::sent($account, HrisAccountSetup::class)->sole()->token;
        auth()->logout();
        $this->post(route('password.update'), [
            'email' => $account->email,
            'token' => $token,
            'password' => 'ApprovedTestPassword4!',
            'password_confirmation' => 'ApprovedTestPassword4!',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $account->email,
            'password' => 'ApprovedTestPassword4!',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $this->get(URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), [
            'id' => $account->id,
            'hash' => sha1($account->email),
        ]))->assertRedirect();
        $account->refresh();
        $this->get(route('dashboard'))->assertRedirect(route('security.edit'));

        $account->forceFill(['two_factor_secret' => encrypt('test-secret'), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($account->refresh())->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('role', 'Department Head')->where('office', 'Office of the Municipal Health Officer'));
    }

    public function test_first_head_bootstrap_requires_execution_and_only_creates_one_account(): void
    {
        $this->seed();
        Notification::fake();
        $input = ['email' => 'head@example.test', '--name' => 'Approved Head', '--approval' => 'TEST-APPROVAL', '--operator' => 'Test operator'];

        $this->artisan('hris:bootstrap-head', $input)->assertSuccessful();
        $this->assertDatabaseCount('users', 0);
        $this->artisan('hris:bootstrap-head', $input + ['--execute' => true])->assertSuccessful();
        $user = User::query()->sole();
        $this->assertSame('HRMO Head', $user->role->name);
        Notification::assertSentTo($user, HrisAccountSetup::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.hrmo_head_bootstrapped', 'model_id' => $user->id]);

        $this->artisan('hris:bootstrap-head', array_replace($input, ['email' => 'other@example.test', '--execute' => true]))
            ->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_employee_accounts_cannot_be_created_through_email_invitations(): void
    {
        $this->seed();
        $actor = User::factory()->create([
            'role_id' => Role::query()->where('name', 'HRMO Head')->value('id'),
            'requires_mfa' => true,
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $employee = Employee::factory()->for(Department::query()->where('code', 'HEALTH')->firstOrFail())->create();
        $this->actingAs($actor)->withSession(['auth.password_confirmed_at' => time()])->post(route('hris.invitations.store'), [
            'employee_number' => $employee->employee_number,
            'email' => 'employee@example.com',
            'role' => 'Employee',
            'approval_reference' => 'TEST-APPROVAL',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 1);
    }
}
