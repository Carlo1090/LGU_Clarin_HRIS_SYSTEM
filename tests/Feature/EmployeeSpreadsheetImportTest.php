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

    public function test_employee_template_matches_employee_fields_and_includes_eligibility_dropdown(): void
    {
        $this->seed();
        $hrmo = User::factory()->create(['role_id' => Role::query()->where('name', 'HRMO Head')->value('id')]);
        $this->actingAs($hrmo)->get(route('hris.employees.import.template'))->assertOk()->assertDownload('EMPLOYEE-BULK-IMPORT-TEMPLATE.xlsx');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(resource_path('templates/EMPLOYEE-BULK-IMPORT-TEMPLATE.xlsx')) === true);
        $workbook = $zip->getFromName('xl/workbook.xml');
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('name="EMPLOYEES"', $workbook);
        $this->assertStringContainsString('name="EligibilityOptions"', $workbook);
        $this->assertStringContainsString('sqref="R2:R5001"', $worksheet);
        $this->assertStringContainsString('<x:formula1>EligibilityOptions</x:formula1>', $worksheet);
        $zip->close();
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
        $token = null;
        $preview = $this->actingAs($actor)->post(route('hris.employees.import.preview'), ['file' => $this->workbook([[
            'Employee Number' => 'EMP-IMPORT-01', 'First Name' => 'Jordan', 'Middle Name' => 'A', 'Last Name' => 'Sample',
            'Gender' => 'female', 'Birth Date' => '1990-05-06', 'Department' => $department->name, 'Position' => 'Nurse I',
            'Suffix' => 'Jr.', 'Employment Status' => 'active', 'Date Hired' => '2015-04-03', 'Date Regularized' => '2017-04-03',
            'Age' => '36', 'TIN' => '001-002-003', 'Date of Original Appointment' => '2015-04-03',
            'Date of Last Promotion/Appointment' => '2020-04-03', 'Appointment Status' => 'P',
            'Civil Service Eligibility' => 'Career Service Professional', 'Comment/Annotation' => 'Verified',
            'PWD' => 'Y', 'Indigenous People' => 'N', 'Solo Parent ID Number' => 'N/A', 'GSIS BP Number' => '0008',
        ]])])->assertRedirect();
        $token = parse_url($preview->headers->get('Location'), PHP_URL_QUERY);
        parse_str((string) $token, $query);
        $token = $query['token'] ?? null;
        $this->actingAs($actor)->get(route('hris.employees.import', ['token' => $token]))->assertOk()->assertInertia(function ($page) use (&$token): void {
            $page->component('hris/import-employees')->has('rows', 1)->where('rows.0.valid', true)->where('rows.0.status', 'active');
            $token = $page->toArray()['props']['token'];
        });

        $password = null;
        $this->post(route('hris.employees.import.store'), ['token' => $token])
            ->assertOk()->assertInertia(function ($page) use (&$password): void {
                $page->where('result.createdProfiles', 1)->where('result.createdAccounts', 1)->has('credentials', 1);
                $password = $page->toArray()['props']['credentials'][0]['temporary_password'];
            });
        $employee = Employee::query()->where('employee_number', 'EMP-IMPORT-01')->firstOrFail();
        $this->assertSame('active', $employee->employment_status);
        $this->assertSame('001-002-003', $employee->tin);
        $this->assertSame('Career Service Professional', $employee->civil_service_eligibility);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame('NURSE-I', $employee->position->code);
        $this->assertDatabaseCount('plantilla_items', 0);
        $this->assertNotNull($password);
        $employeeUser = User::query()->where('employee_id', $employee->id)->firstOrFail();
        $employeeUser->forceFill(['must_change_password' => false, 'requires_mfa' => false])->save();
        $this->actingAs($employeeUser)->get(route('hris.my-profile'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('employee.department', $department->name)
            ->where('employee.position_title', 'Nurse I')
            ->where('employee.employment_status', 'active')
            ->where('employee.civil_service_eligibility', 'Career Service Professional'));
    }

    private function workbook(array $rows): UploadedFile
    {
        $headers = ['Employee Number', 'First Name', 'Middle Name', 'Last Name', 'Suffix', 'Gender', 'Birth Date', 'Department', 'Position', 'Employment Status', 'Date Hired', 'Date Regularized', 'Age', 'TIN', 'Date of Original Appointment', 'Date of Last Promotion/Appointment', 'Appointment Status', 'Civil Service Eligibility', 'Comment/Annotation', 'PWD', 'Indigenous People', 'Solo Parent ID Number', 'GSIS BP Number', 'Email Address'];
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
