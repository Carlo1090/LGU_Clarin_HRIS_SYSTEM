<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeeImportTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

class EmployeeSpreadsheetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_hrmo_head_sees_the_employee_bulk_import_action(): void
    {
        $this->seed();
        $hrmo = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $hrOfficer = User::factory()->create(['role_id' => Role::query()->where('name', 'HR Officer')->value('id')]);

        $this->actingAs($hrmo)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()->assertInertia(fn ($page) => $page->where('canImport', true));
        $this->actingAs($hrOfficer)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()->assertInertia(fn ($page) => $page->where('canImport', false));
        $this->actingAs($hrOfficer)->get(route('hris.employees.import'))->assertForbidden();
    }

    public function test_hris_department_directory_shows_all_fixed_offices(): void
    {
        $this->seed();
        $actor = User::factory()->create(['role_id' => Role::query()->where('name', 'HR Officer')->value('id')]);
        $this->actingAs($actor)->get(route('hris.resources.index', ['resource' => 'departments']))
            ->assertOk()->assertInertia(fn ($page) => $page->has('records.data', 14)->where('officeDirectory', true));
    }

    public function test_department_directory_keeps_existing_legacy_departments_visible(): void
    {
        $this->seed();
        $legacy = Department::query()->create(['code' => 'HRMO-LEGACY', 'name' => 'Legacy HRMO']);
        $actor = User::factory()->create(['role_id' => Role::query()->where('name', 'HR Officer')->value('id')]);

        $this->actingAs($actor)->get(route('hris.resources.index', ['resource' => 'departments']))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->has('records.data', 15)
            ->where('records.data.0.code', $legacy->code)
            ->where('records.data.0.name', $legacy->name));
    }

    public function test_employee_template_contains_minimal_account_creation_fields(): void
    {
        $this->seed();
        $hrmo = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $this->actingAs($hrmo)->get(route('hris.employees.import.template'))->assertOk()->assertDownload('EMPLOYEE-BULK-IMPORT-TEMPLATE.xlsx');
        $path = sys_get_temp_dir().'/employee-template-'.uniqid().'.xlsx';
        app(EmployeeImportTemplate::class)->download($path, app(EmployeeImportTemplate::class)->statuses());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $workbook = $zip->getFromName('xl/workbook.xml');
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('name="EMPLOYEES"', $workbook);
        $this->assertStringNotContainsString('EligibilityOptions', $workbook);
        $this->assertStringContainsString('name="Departments"', $workbook);
        $this->assertStringContainsString('Departments!$A$2:$A$15', $workbook);
        $this->assertStringContainsString('<x:formula1>DepartmentOptions</x:formula1>', $worksheet);
        $this->assertStringContainsString('Office of the Municipal Mayor', $zip->getFromName('xl/worksheets/sheet2.xml'));
        $this->assertStringContainsString('<x:formula1>"active,on leave,inactive,terminated"</x:formula1>', $worksheet);
        $this->assertStringContainsString('Employee Number', $worksheet);
        $this->assertStringContainsString('Department', $worksheet);
        $zip->close();
        unlink($path);
    }

    public function test_template_popup_updates_the_downloaded_status_dropdown(): void
    {
        $this->seed();
        $hrmo = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $path = sys_get_temp_dir().'/employee-template-'.uniqid().'.xlsx';
        $configPath = storage_path('app/private/employee-import-columns.json');
        $previousConfig = is_file($configPath) ? file_get_contents($configPath) : null;

        try {
            $this->actingAs($hrmo)->put(route('hris.employees.import.template.update'), [
                'columns' => app(EmployeeImportTemplate::class)->columns(),
                'status_options' => ['Active', 'Seasonal'],
            ])->assertRedirect();

            app(EmployeeImportTemplate::class)->download($path, app(EmployeeImportTemplate::class)->statuses());
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('<x:formula1>"Active,Seasonal"</x:formula1>', $sheet);
            $zip->close();
            $this->actingAs($hrmo)->get(route('hris.employees.import'))->assertOk()->assertInertia(fn ($page) => $page
                ->where('statusOptions.0', 'Active')
                ->where('statusOptions.1', 'Seasonal'));

            $this->actingAs($hrmo)->get(route('hris.employees.import.template'))
                ->assertOk()->assertDownload('EMPLOYEE-BULK-IMPORT-TEMPLATE.xlsx');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            if ($previousConfig === null) {
                if (is_file($configPath)) {
                    unlink($configPath);
                }
            } else {
                file_put_contents($configPath, $previousConfig, LOCK_EX);
            }
        }
    }

    public function test_employee_workbook_imports_employee_profile_and_creates_login_without_email(): void
    {
        $this->withoutVite();
        $this->seed();
        $department = Department::query()->where('code', 'HEALTH')->firstOrFail();
        Position::query()->create(['department_id' => $department->id, 'title' => 'Nurse I', 'code' => 'NURSE-I']);
        $actor = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $preview = $this->actingAs($actor)->post(route('hris.employees.import.preview'), ['file' => $this->workbook([[
            'Employee Number' => 'EMP-IMPORT-01', 'First Name' => 'Jordan', 'Middle Name' => 'A', 'Last Name' => 'Sample',
            'Department' => $department->name, 'Position' => 'Nurse I', 'Suffix' => 'Jr.', 'Employment Status' => 'active',
        ]])])->assertRedirect();
        $token = parse_url($preview->headers->get('Location'), PHP_URL_QUERY);
        parse_str((string) $token, $query);
        $token = $query['token'] ?? null;
        $this->get(route('hris.employees.import', ['token' => $token]))->assertOk()->assertInertia(fn ($page) => $page
            ->component('hris/import-employees')->has('rows', 1)->where('rows.0.valid', true)
            ->where('rows.0.account_action', 'Create account')->missing('rows.0.temporary_password')
            ->missingFlash('activationSlips'));

        $this->post(route('hris.employees.import.store'), ['token' => $token])->assertSessionHasErrors('password');
        $response = $this->withHeader('X-Inertia', 'true')->post(route('hris.employees.import.store'), [
            'token' => $token, 'expiry_days' => 21, 'password' => 'password',
        ])->assertOk()->assertJsonPath('props.created', 1)->assertHeader('Cache-Control', 'no-store, private');
        $slip = $response->json('flash.activationSlips.0');
        $this->assertSame('EMP-IMPORT-01', $slip['employee_number']);
        $this->assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z2-9]{4}-[A-HJ-KM-NP-Z2-9]{4}$/', $slip['activation_code']);
        $employee = Employee::query()->where('employee_number', 'EMP-IMPORT-01')->firstOrFail();
        $this->assertSame('active', $employee->employment_status);
        $this->assertNull($employee->tin);
        $this->assertNull($employee->civil_service_eligibility);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame('NURSE-I', $employee->position->code);
        $this->assertDatabaseCount('plantilla_items', 0);
        $account = $employee->user;
        $this->assertNull($account->password);
        $this->assertSame($employee->employee_number, $account->username);
        $activation = $account->activationCodes()->firstOrFail();
        $this->assertTrue(Hash::check(str_replace('-', '', $slip['activation_code']), $activation->code_hash));
        $this->assertSame(now()->addDays(21)->toDateString(), $activation->expires_at->toDateString());
        $this->flushHeaders()->get(route('hris.employees.import'))->assertOk()->assertInertia(fn ($page) => $page->missingFlash('activationSlips'));
    }

    public function test_employee_workbook_requires_only_core_account_fields(): void
    {
        $this->withoutVite();
        $this->seed();
        $department = Department::query()->where('code', 'HEALTH')->firstOrFail();
        Position::query()->create(['department_id' => $department->id, 'title' => 'Nurse I', 'code' => 'NURSE-I']);
        $actor = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $response = $this->actingAs($actor)->post(route('hris.employees.import.preview'), ['file' => $this->workbook([[
            'Employee Number' => 'EMP-IMPORT-02', 'First Name' => 'Jordan', 'Last Name' => 'Sample',
            'Department' => $department->name, 'Employment Status' => 'active',
        ], [
            'Employee Number' => 'EMP-IMPORT-03', 'First Name' => 'Taylor', 'Last Name' => 'Missing Department',
            'Employment Status' => 'active',
        ]])])->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->actingAs($actor)->get(route('hris.employees.import', ['token' => $query['token']]))->assertOk()
            ->assertInertia(fn ($page) => $page->has('rows', 2)
                ->where('rows.0.valid', true)->where('rows.0.account_action', 'Create account')
                ->where('rows.1.valid', false)->where('rows.1.errors.0', 'Department is required.'));

        $this->post(route('hris.employees.import.store'), ['token' => $query['token'], 'password' => 'password'])
            ->assertOk()->assertInertia(fn ($page) => $page->where('created', 1)->where('rejected.0.row', 3));
        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-IMPORT-02']);
        $this->assertDatabaseMissing('employees', ['employee_number' => 'EMP-IMPORT-03']);
    }

    public function test_employee_import_skips_an_existing_employee_without_changing_it(): void
    {
        $this->withoutVite();
        $this->seed();
        $department = Department::query()->where('code', 'HEALTH')->firstOrFail();
        $position = Position::query()->create(['department_id' => $department->id, 'title' => 'Nurse I', 'code' => 'NURSE-I']);
        $employee = Employee::factory()->create([
            'employee_number' => 'EMP-EXISTING-01', 'department_id' => $department->id, 'position_id' => $position->id,
        ]);
        $actor = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $response = $this->actingAs($actor)->post(route('hris.employees.import.preview'), ['file' => $this->workbook([[
            'Employee Number' => $employee->employee_number, 'First Name' => $employee->first_name, 'Middle Name' => 'A',
            'Last Name' => $employee->last_name, 'Suffix' => 'Jr.', 'Gender' => 'female', 'Birth Date' => '1990-05-06',
            'Department' => $department->name, 'Position' => $position->title, 'Employment Status' => 'active',
            'Date Hired' => '2015-04-03', 'Date Regularized' => '2017-04-03', 'Age' => '36', 'TIN' => '001-002-003',
            'Date of Original Appointment' => '2015-04-03', 'Date of Last Promotion/Appointment' => '2020-04-03',
            'Appointment Status' => 'P', 'Civil Service Eligibility' => 'Career Service Professional',
            'Comment/Annotation' => 'Verified', 'PWD' => 'Y', 'Indigenous People' => 'N',
            'Solo Parent ID Number' => 'N/A', 'GSIS BP Number' => '0008',
        ]])])->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->actingAs($actor)->get(route('hris.employees.import', ['token' => $query['token']]))->assertOk()
            ->assertInertia(fn ($page) => $page->where('rows.0.valid', false)
                ->where('rows.0.profile_action', 'Already exists')->where('rows.0.account_action', 'Skip'));
        $token = $query['token'];

        $this->actingAs($actor)->post(route('hris.employees.import.store'), ['token' => $token, 'password' => 'password'])->assertOk()
            ->assertInertia(fn ($page) => $page->where('created', 0)->has('rejected', 1)->where('rejected.0.row', 2));
        $this->assertSame(1, Employee::query()->where('employee_number', $employee->employee_number)->count());
        $this->assertDatabaseMissing('users', ['employee_id' => $employee->id]);
    }

    private function workbook(array $rows): UploadedFile
    {
        $headers = ['Employee Number', 'First Name', 'Middle Name', 'Last Name', 'Suffix', 'Department', 'Position', 'Employment Status', 'Email Address'];
        $path = tempnam(sys_get_temp_dir(), 'employees-').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="EMPLOYEES" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheetRows = [$headers];
        foreach ($rows as $row) {
            $sheetRows[] = array_map(fn ($header) => $row[$header] ?? '', $headers);
        }
        $xml = '';
        foreach ($sheetRows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="'.$rowNumber.'">';
            foreach ($cells as $index => $value) {
                if ($value === '') {
                    continue;
                }
                $column = '';
                for ($number = $index + 1; $number > 0; $number = intdiv($number - 1, 26)) {
                    $column = chr(65 + (($number - 1) % 26)).$column;
                }
                $xml .= '<c r="'.$column.$rowNumber.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xml.'</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, 'employees.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);
    }
}
