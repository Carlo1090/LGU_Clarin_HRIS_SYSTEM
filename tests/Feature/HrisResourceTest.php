<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HrisResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_hr_officer_can_view_and_create_a_department(): void
    {
        $role = Role::query()->create(['name' => 'HR Officer', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id])->save();

        $this->actingAs($user)
            ->get(route('hris.resources.index', ['resource' => 'departments']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('hris/resources/index')
                ->where('title', 'Departments')
            );

        $this->actingAs($user)
            ->post(route('hris.resources.store', ['resource' => 'departments']), [
                'name' => 'Human Resource Management Office',
                'code' => 'HRMO',
                'parent_department_id' => null,
                'description' => 'Human resources administration',
            ])
            ->assertRedirect(route('hris.resources.index', ['resource' => 'departments']));

        $this->assertDatabaseHas('departments', ['code' => 'HRMO']);
    }

    public function test_users_without_an_hris_management_role_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('hris.resources.index', ['resource' => 'departments']))
            ->assertForbidden();
    }

    public function test_database_seeder_registers_the_organization_roles(): void
    {
        $this->seed();

        $roles = DB::table('roles')->whereIn('name', [
            'Employee',
            'Department Head',
            'HR Officer',
            'HRMO Head',
            'Payroll Officer',
            'Accountant',
            'Treasurer',
            'Mayor',
            'MIS Administrator',
            'DPO',
            'Auditor',
        ])->pluck('name')->all();

        $this->assertCount(11, $roles);
    }

    public function test_seeded_demo_account_can_authenticate_through_login_route(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_dashboard_uses_the_assigned_role(): void
    {
        $role = Role::query()->create(['name' => 'HRMO Head', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('role', 'HRMO Head')
                ->has('cards', 4)
            );
    }

    public function test_department_heads_can_view_but_not_edit_hris_records(): void
    {
        $role = Role::query()->create(['name' => 'Department Head', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id])->save();

        $this->actingAs($user)
            ->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('hris.resources.store', ['resource' => 'employees']), [])
            ->assertForbidden();
    }

    public function test_department_head_employee_list_is_limited_to_their_department(): void
    {
        $role = Role::query()->create(['name' => 'Department Head', 'guard_name' => 'web']);
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $user = User::factory()->create();
        $headEmployee = Employee::factory()->create(['department_id' => $department->id]);
        $otherEmployee = Employee::factory()->create(['department_id' => $otherDepartment->id]);
        $user->forceFill(['role_id' => $role->id, 'employee_id' => $headEmployee->id])->save();

        $this->actingAs($user)
            ->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('records.data.0.id', $headEmployee->id)
                ->has('records.data', 1)
            );

        $this->assertNotSame($headEmployee->id, $otherEmployee->id);
    }

    public function test_payroll_officer_access_is_limited_to_payroll_resources(): void
    {
        $role = Role::query()->create(['name' => 'Payroll Officer', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id])->save();

        $this->actingAs($user)
            ->get(route('hris.resources.index', ['resource' => 'payrolls']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('hrisNavigation', 2)
                ->where('hrisNavigation.0.href', '/hris/payroll-periods')
                ->where('hrisNavigation.1.href', '/hris/payrolls')
            );

        $this->actingAs($user)
            ->get(route('hris.resources.index', ['resource' => 'departments']))
            ->assertForbidden();
    }
}
