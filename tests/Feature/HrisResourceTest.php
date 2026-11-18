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

    public function test_hr_officer_can_view_but_cannot_create_a_department(): void
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
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['code' => 'HRMO']);
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

    public function test_seeder_registers_only_the_fixed_offices_and_creates_no_accounts(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('departments', 14);
        $this->assertDatabaseHas('departments', [
            'code' => 'TREASURER',
            'name' => 'Office of the Municipal Treasurer',
        ]);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('departments', ['code' => 'TREASURER', 'name' => 'Office of the Municipal Treasurer']);
    }

    public function test_seeder_preserves_existing_department_and_employee_assignment(): void
    {
        $legacy = Department::query()->create(['code' => 'MTO', 'name' => 'Municipal Treasury Office']);
        $employee = Employee::factory()->for($legacy)->create();

        $this->seed();

        $this->assertDatabaseHas('departments', ['id' => $legacy->id, 'code' => 'MTO']);
        $this->assertSame($legacy->id, $employee->refresh()->department_id);
        $this->assertDatabaseHas('departments', ['code' => 'TREASURER']);
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

    public function test_plantilla_form_saves_employee_columns_to_the_linked_employee_profile(): void
    {
        $this->seed();
        $role = Role::query()->where('name', 'HRMO Head')->firstOrFail();
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id])->save();
        $department = Department::query()->where('code', 'MAYOR')->firstOrFail();
        $employee = Employee::factory()->create(['employee_number' => '000123', 'department_id' => $department->id]);

        $response = $this->actingAs($user)->post(route('hris.resources.store', ['resource' => 'plantilla-items']), [
            'department_code' => 'MAYOR', 'employee_number' => '000123', 'organizational_unit' => 'Office of the Mayor',
            'item_number' => '001', 'position_title' => 'Custom Position', 'salary_grade' => '12',
            'authorized_annual_salary' => '300000.00', 'actual_annual_salary' => '310000.00', 'step' => '2',
            'last_name' => 'Employee', 'first_name' => 'Test', 'middle_name' => 'A', 'sex' => 'F',
            'date_of_birth' => '1990-01-02', 'age' => '36', 'tin' => '001-002-003',
            'date_of_original_appointment' => '2015-03-04', 'date_of_last_promotion_appointment' => '2020-05-06',
            'appointment_status' => 'P', 'civil_service_eligibility' => 'Custom Eligibility',
            'comment_annotation' => 'Profile note', 'pwd' => 'Y', 'indigenous_people' => '',
            'solo_parent_id_number' => '0009', 'abolished' => '', 'dissolved' => '', 'gsis_bp_number' => '0008',
            'position_classification' => '2nd Level',
        ]);

        $response->assertRedirect(route('hris.resources.index', ['resource' => 'plantilla-items']));
        $this->assertDatabaseHas('plantilla_items', [
            'department_code' => 'MAYOR', 'employee_id' => $employee->id, 'item_number' => '001',
            'position_title' => 'Custom Position', 'salary_grade' => 12,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id, 'first_name' => 'Test', 'last_name' => 'Employee', 'gender' => 'F',
            'tin' => '001-002-003', 'appointment_status' => 'P', 'civil_service_eligibility' => 'Custom Eligibility',
            'solo_parent_id_number' => '0009', 'gsis_bp_number' => '0008',
        ]);

    }

    public function test_employee_can_view_only_their_own_plantilla_profile(): void
    {
        $this->seed();
        $role = Role::query()->where('name', 'Employee')->firstOrFail();
        $employee = Employee::factory()->create(['employee_number' => 'E-1']);
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id, 'employee_id' => $employee->id])->save();

        $this->actingAs($user)
            ->get(route('hris.my-profile'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('hris/my-profile')
                ->where('employee.employee_number', 'E-1')
            );

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['employee_number' => 'E-2']);
        $otherUser->forceFill(['role_id' => $role->id, 'employee_id' => $otherEmployee->id])->save();
        $this->actingAs($user)->get(route('hris.my-profile'))->assertInertia(fn ($page) => $page->where('employee.employee_number', 'E-1'));
    }

    public function test_hrmo_head_can_download_the_plantilla_workbook(): void
    {
        $this->seed();
        $role = Role::query()->where('name', 'HRMO Head')->firstOrFail();
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role->id])->save();

        $path = storage_path('app/private/TEMPLATE_HRMO-AND-PLANTILLA-IMPORT.xlsx');
        $created = ! is_file($path);
        if ($created) {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }
            file_put_contents($path, 'test workbook');
        }
        try {
            $this->actingAs($user)
                ->get(route('hris.plantilla.template'))
                ->assertOk()
                ->assertDownload('TEMPLATE_HRMO-AND-PLANTILLA-IMPORT.xlsx');
        } finally {
            if ($created) {
                unlink($path);
            }
        }
    }

    public function test_employee_directory_is_sorted_by_employment_status_and_shows_summary(): void
    {
        $this->seed();
        $role = Role::query()->where('name', 'HRMO Head')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        Employee::factory()->create(['employee_number' => 'EMP-INACTIVE', 'first_name' => 'Taylor', 'last_name' => 'Same', 'employment_status' => 'inactive']);
        Employee::factory()->create(['employee_number' => 'EMP-ACTIVE', 'first_name' => 'Alex', 'last_name' => 'Same', 'employment_status' => 'active']);

        $this->actingAs($user)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('hris/resources/index')
            ->where('fields.9.name', 'employment_status')
            ->where('records.data.0.employee_number', 'EMP-ACTIVE')
            ->where('records.data.0.employment_status', 'active')
            ->where('statusSummary.0.status', 'active')
            ->where('statusSummary.0.total', 1)
            );
    }

    public function test_employee_record_form_uses_the_excel_status_dropdown_choices(): void
    {
        $this->seed();
        $user = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);

        $this->actingAs($user)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->where('fields.9.type', 'select')
            ->has('fields.9.options', 4)
            ->where('fields.9.options.0.value', 'active')
            ->where('fields.9.options.0.label', 'Active')
            ->where('fields.9.options.3.value', 'terminated')
            ->where('fields.10.type', 'select')
            ->where('fields.10.options.0.value', 'P')
            ->where('fields.10.options.0.label', 'Permanent')
            ->where('fields.10.options.4.label', 'Coterminous')
            ->where('fields.10.options.5.label', 'Presidential Appointee')
            ->where('fields.10.options.6.value', 'FT')
            ->has('fields.10.options', 7));
    }

    public function test_manual_employee_creation_rejects_status_values_outside_the_dropdowns(): void
    {
        $this->seed();
        $user = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $url = route('hris.resources.store', ['resource' => 'employees']);
        $record = [
            'employee_number' => 'EMP-MANUAL-01', 'first_name' => 'Jordan', 'last_name' => 'Sample',
            'employment_status' => 'unknown', 'appointment_status' => 'unknown',
        ];

        $this->actingAs($user)->post($url, $record)->assertSessionHasErrors(['employment_status', 'appointment_status']);
        $this->actingAs($user)->post($url, array_merge($record, ['employment_status' => 'active', 'appointment_status' => 'CT']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-MANUAL-01', 'appointment_status' => 'CT']);
    }
}
