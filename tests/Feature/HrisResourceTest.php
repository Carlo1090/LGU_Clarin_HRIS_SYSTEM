<?php

namespace Tests\Feature;

use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
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

    public function test_employee_directory_search_finds_photos_across_pages_and_obeys_department_scope(): void
    {
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $department = Department::factory()->create();
        Employee::factory()->count(21)->create(['department_id' => $department->id, 'last_name' => 'Adams']);
        $account = User::factory()->create();
        $account->forceFill(['avatar' => '/profile-photos/example.png'])->save();
        $target = Employee::factory()->create(['department_id' => $department->id, 'user_id' => $account->id, 'first_name' => 'Quentin', 'last_name' => 'Zuniga']);
        $outsider = Employee::factory()->create(['first_name' => 'Quentin', 'last_name' => 'Zuniga']);
        $this->actingAs($hr)->get('/hris/employees?column=employee_number&q='.$target->employee_number)
            ->assertInertia(fn ($page) => $page->has('records.data', 1)
                ->where('records.data.0.id', $target->id)->where('records.data.0.avatar', '/profile-photos/example.png'));

        $target->update(['user_id' => null]);
        $account->forceFill(['employee_id' => $target->id])->save();
        $head = User::factory()->for(Role::factory()->state(['name' => 'Department Head']))->create(['employee_id' => $target->id]);
        $this->actingAs($head)->get('/hris/employees?q=Quentin%20Zuniga')
            ->assertInertia(fn ($page) => $page->has('records.data', 1)->where('records.data.0.id', $target->id)->where('records.data.0.avatar', '/profile-photos/example.png'));
        $this->get('/hris/employees?column=employee_number&q='.$outsider->employee_number)
            ->assertInertia(fn ($page) => $page->has('records.data', 0));
    }

    public function test_assign_biometric_id_filter_shows_only_unlinked_employees(): void
    {
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $department = Department::factory()->create();
        $unlinked = Employee::factory()->create([
            'department_id' => $department->id,
            'biometric_id' => null,
        ]);
        $linked = Employee::factory()->create([
            'department_id' => $department->id,
            'biometric_id' => 123,
        ]);

        $this->actingAs($hr)
            ->get('/hris/employees?assign_biometric_id=123')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('assignBiometricId', 123)
                ->has('records.data', 1)
                ->where('records.data.0.id', $unlinked->id));
    }

    public function test_attendance_lists_employees_once_with_dtr_links_and_respects_department_scope(): void
    {
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $employee = Employee::factory()->create(['first_name' => 'Alex', 'last_name' => 'Adams', 'suffix' => null]);
        $withoutEntries = Employee::factory()->create(['last_name' => 'Zulu']);
        AttendanceRecord::factory()->count(2)->for($employee)->create();

        $this->actingAs($hr)->get('/hris/attendance?month=2026-10')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('hris/resources/index')
                ->where('attendanceDirectory', true)
                ->has('records.data', 2)
                ->where('records.data.0.name', 'Adams, Alex')
                ->where('attendanceMonth', '2026-10')
                ->where('records.data.0.dtr_url', "/hris/attendance/dtr/{$employee->id}?month=2026-10")
                ->where('records.data.1.dtr_url', "/hris/attendance/dtr/{$withoutEntries->id}?month=2026-10")
                ->where('records.data.1.attendance_complete_days', 0)
                ->where('records.data.1.attendance_review_days', 0));

        $head = User::factory()->for(Role::factory()->state(['name' => 'Department Head']))->create(['employee_id' => $employee->id]);
        $this->actingAs($head)->get('/hris/attendance')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('records.data', 1)
                ->where('records.data.0.id', $employee->id));
        $this->getJson("/hris/attendance/dtr/{$withoutEntries->id}")->assertForbidden();
    }

    public function test_attendance_indicators_count_each_recorded_workday_once_for_the_selected_month(): void
    {
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $employee = Employee::factory()->create();
        $employee->forceFill(['biometric_id' => 15])->save();
        $batch = AttendanceImport::query()->create(['file_name' => 'test.dat', 'status' => 'completed']);
        foreach (['19 07:50:00', '19 12:03:00', '19 12:50:00', '19 17:02:00', '20 07:54:00', '22 07:55:00'] as $device => $punch) {
            AttendancePunch::query()->insert([
                'attendance_import_id' => $batch->id, 'import_batch_id' => $batch->id,
                'biometric_id' => 15, 'machine_user_id' => 15, 'device_id' => $device,
                'punched_at' => '2026-10-'.$punch, 'created_at' => now(),
            ]);
        }
        foreach ([
            ['2026-10-21', 'absent', null, null],
            ['2026-10-21', 'present', '2026-10-21 08:00:00', '2026-10-21 17:00:00'],
            ['2026-10-22', 'On Leave', null, null],
            ['2026-10-23', 'late', '2026-10-23 09:00:00', '2026-10-23 17:00:00'],
            ['2026-11-02', 'incomplete', '2026-11-02 08:00:00', null],
        ] as [$date, $status, $in, $out]) {
            AttendanceRecord::factory()->for($employee)->create([
                'attendance_date' => $date, 'status' => $status, 'time_in' => $in, 'time_out' => $out,
            ]);
        }

        $this->actingAs($hr)->get('/hris/attendance?month=2026-10')
            ->assertInertia(fn ($page) => $page
                ->where('records.data.0.attendance_complete_days', 2)
                ->where('records.data.0.attendance_review_days', 2));
        $this->get('/hris/attendance?month=2026-11')
            ->assertInertia(fn ($page) => $page
                ->where('records.data.0.attendance_complete_days', 0)
                ->where('records.data.0.attendance_review_days', 1));
    }

    public function test_attendance_entries_can_still_be_managed_from_the_entries_view(): void
    {
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $employee = Employee::factory()->create();
        $entriesUrl = '/hris/attendance?view=entries';
        $data = [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-19',
            'total_hours' => 8,
            'status' => 'present',
            'source' => 'manual',
            'overtime_hours' => 0,
        ];

        $this->actingAs($hr)->post('/hris/attendance', $data)->assertRedirect($entriesUrl);
        $entry = AttendanceRecord::query()->sole();
        $this->get($entriesUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canManage', true)
                ->where('records.data.0.id', $entry->id));
        $entryUrl = "/hris/attendance/{$entry->id}";
        $this->put($entryUrl, ['total_hours' => 7] + $data)->assertRedirect($entriesUrl);
        $this->delete($entryUrl)->assertRedirect($entriesUrl);
        $this->assertModelMissing($entry);
    }

    public function test_saved_calendar_rules_no_longer_influence_attendance_or_dtr_previews(): void
    {
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $employee = Employee::factory()->create(['date_hired' => '2020-01-01', 'employment_status' => 'active']);
        DB::table('attendance_schedules')->insert([
            'scope_key' => 'municipal', 'effective_from' => '2020-01-01', 'workdays' => '[1,2,3,4,5]',
            'am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00',
        ]);

        $this->actingAs($hr)->get('/hris/attendance/work-calendar')->assertNotFound();
        $this->get('/hris/attendance?month=2026-10')->assertInertia(fn ($page) => $page
            ->where('records.data.0.attendance_complete_days', 0)
            ->where('records.data.0.attendance_review_days', 0));
        $this->getJson("/hris/attendance/dtr/{$employee->id}?month=2026-10")->assertOk()->assertJsonCount(0, 'records');
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
            'employee_number' => '0042', 'first_name' => 'Jo', 'last_name' => 'Sample',
            'department_id' => Department::factory()->create()->id,
            'employment_status' => 'unknown', 'appointment_status' => 'unknown',
        ];

        $this->actingAs($user)->post($url, $record)->assertSessionHasErrors(['employment_status', 'appointment_status']);
        $this->actingAs($user)->post($url, array_merge($record, ['employment_status' => 'active', 'appointment_status' => 'CT']))
            ->assertRedirect(route('hris.resources.index', ['resource' => 'employees']));
        $employee = Employee::query()->where('employee_number', '0042')->firstOrFail();
        $account = User::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('CT', $employee->appointment_status);
        $this->assertNull($account->email);
        $this->assertTrue($account->must_change_password);
        $this->actingAs($user)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->where('createdAccountCredentials.username', '0042')
            ->where('createdAccountCredentials.employee_number', '0042')
            ->where('createdAccountCredentials.reference_code', 'JO0042')
            ->has('createdAccountCredentials.temporary_password'));

        $account->forceFill(['email' => 'old@example.com', 'email_verified_at' => now()])->save();
        $this->actingAs($user)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->where('records.data.0.account_email', 'old@example.com'));

        User::factory()->create(['email' => 'taken@example.com']);
        $updateUrl = route('hris.resources.update', ['resource' => 'employees', 'record' => $employee->id]);
        $record = array_merge($record, ['employment_status' => 'active', 'appointment_status' => 'CT']);
        $this->put($updateUrl, $record + ['account_email' => 'TAKEN@example.com'])->assertSessionHasErrors('account_email');
        $this->assertSame('old@example.com', $account->fresh()->email);
        $this->put($updateUrl, $record + ['account_email' => 'NEW@example.com'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('new@example.com', $account->fresh()->email);
        $this->assertNull($account->fresh()->email_verified_at);
    }
}
