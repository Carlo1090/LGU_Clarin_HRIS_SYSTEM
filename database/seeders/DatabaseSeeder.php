<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EvaluationPeriod;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PerformanceCommitment;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roleDescriptions = [
            'Employee' => 'Personal profile, attendance, leave credits, payslips, and requests.',
            'Department Head' => 'Department employees, attendance, pending approvals, and performance.',
            'HR Officer' => 'Employee records, incomplete 201 files, attendance issues, and leave requests.',
            'HRMO Head' => 'Total employees, department statistics, pending HR transactions, and reports.',
            'Payroll Officer' => 'Payroll periods, payroll preparation, errors, deductions, and payslips.',
            'Accountant' => 'Approved payroll, payroll totals, deductions, and reconciliation.',
            'Treasurer' => 'Approved payroll, disbursement information, and payment status.',
            'Mayor' => 'Employee totals, department staffing, vacancies, and HR summaries.',
            'MIS Administrator' => 'User accounts, permissions, system health, backups, and technical logs.',
            'DPO' => 'Privacy requests, access logs, and privacy incidents.',
            'Auditor' => 'Audit records, transaction history, and payroll evidence.',
        ];
        $roles = [];

        foreach ($roleDescriptions as $name => $description) {
            $roles[$name] = Role::updateOrCreate(
                ['name' => $name],
                ['guard_name' => 'web', 'description' => $description]
            );
        }

        $hrRole = $roles['HR Officer'];
        $hrmoHeadRole = $roles['HRMO Head'];
        $employeeRole = $roles['Employee'];

        $hrDepartment = Department::firstOrCreate(
            ['code' => 'HRMO'],
            ['name' => 'Human Resource Management Office', 'description' => 'Human resources administration']
        );
        $financeDepartment = Department::firstOrCreate(
            ['code' => 'MTO'],
            ['name' => 'Municipal Treasury Office', 'description' => 'Municipal finance and payroll']
        );

        $hrPosition = Position::firstOrCreate(
            ['code' => 'HR-001'],
            ['department_id' => $hrDepartment->id, 'title' => 'HR Officer', 'job_grade' => 'SG-15', 'is_active' => true]
        );
        $staffPosition = Position::firstOrCreate(
            ['code' => 'HR-002'],
            ['department_id' => $hrDepartment->id, 'title' => 'Administrative Assistant', 'job_grade' => 'SG-8', 'is_active' => true]
        );
        $financePosition = Position::firstOrCreate(
            ['code' => 'MTO-001'],
            ['department_id' => $financeDepartment->id, 'title' => 'Payroll Officer', 'job_grade' => 'SG-12', 'is_active' => true]
        );

        $admin = User::query()->where('email', 'admin@example.com')->first()
            ?? User::factory()->create([
                'name' => 'HRIS Administrator',
                'email' => 'admin@example.com',
                'password' => 'password',
            ]);
        $admin->forceFill(['role_id' => $hrmoHeadRole->id, 'is_active' => true])->save();

        $hrUser = User::query()->where('email', 'hr@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Sample HR Officer',
                'email' => 'hr@example.com',
                'password' => 'password',
            ]);
        $hrUser->forceFill(['role_id' => $hrRole->id, 'is_active' => true])->save();
        $hrEmployee = Employee::query()->where('employee_number', 'EMP-0001')->first()
            ?? Employee::factory()->create([
                'employee_number' => 'EMP-0001',
                'department_id' => $hrDepartment->id,
                'position_id' => $hrPosition->id,
                'first_name' => 'Sample',
                'last_name' => 'HR Officer',
                'employment_status' => 'active',
                'date_hired' => '2020-01-15',
            ]);
        $hrEmployee->forceFill(['user_id' => $hrUser->id])->save();
        $hrUser->forceFill(['employee_id' => $hrEmployee->id])->save();

        $employeeUser = User::query()->where('email', 'employee@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Sample Employee',
                'email' => 'employee@example.com',
                'password' => 'password',
            ]);
        $employeeUser->forceFill(['role_id' => $employeeRole->id, 'is_active' => true])->save();
        $employee = Employee::query()->where('employee_number', 'EMP-0002')->first()
            ?? Employee::factory()->create([
                'employee_number' => 'EMP-0002',
                'department_id' => $financeDepartment->id,
                'position_id' => $financePosition->id,
                'first_name' => 'Sample',
                'last_name' => 'Employee',
                'employment_status' => 'active',
                'date_hired' => '2022-06-01',
            ]);
        $employee->forceFill(['user_id' => $employeeUser->id])->save();
        $employeeUser->forceFill(['employee_id' => $employee->id])->save();

        if (! $employee->educations()->exists()) {
            EmployeeEducation::create([
                'employee_id' => $employee->id,
                'level' => 'Bachelor',
                'school_name' => 'Clarin Community College',
                'degree' => 'Bachelor of Public Administration',
                'course' => 'Public Administration',
                'year_graduated' => 2020,
            ]);
        }

        $leaveType = LeaveType::query()->where('code', 'VL')->first()
            ?? LeaveType::factory()->create([
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'default_days' => 15,
                'is_paid' => true,
                'is_active' => true,
            ]);
        LeaveCredit::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => now()->year],
            ['earned_days' => 15, 'used_days' => 0, 'remaining_days' => 15]
        );

        if (! AttendanceRecord::query()->where('employee_id', $employee->id)->whereDate('attendance_date', now()->toDateString())->exists()) {
            AttendanceRecord::factory()->create([
                'employee_id' => $employee->id,
                'attendance_date' => now()->toDateString(),
                'time_in' => now()->setTime(8, 0),
                'time_out' => now()->setTime(17, 0),
            ]);
        }

        $payrollPeriod = PayrollPeriod::query()->where('code', 'DEMO-'.now()->format('Ym'))->first()
            ?? PayrollPeriod::factory()->create([
                'code' => 'DEMO-'.now()->format('Ym'),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => 'draft',
            ]);
        Payroll::query()->where('employee_id', $employee->id)->where('payroll_period_id', $payrollPeriod->id)->first()
            ?? Payroll::factory()->create([
                'employee_id' => $employee->id,
                'payroll_period_id' => $payrollPeriod->id,
                'gross_pay' => 25000,
                'net_pay' => 25000,
                'status' => 'draft',
            ]);

        $vacancy = Vacancy::query()->where('title', 'Administrative Assistant')->where('department_id', $hrDepartment->id)->first()
            ?? Vacancy::factory()->create([
                'title' => 'Administrative Assistant',
                'department_id' => $hrDepartment->id,
                'position_id' => $staffPosition->id,
                'open_positions' => 1,
                'status' => 'open',
            ]);
        Applicant::query()->where('vacancy_id', $vacancy->id)->where('email', 'applicant@example.com')->first()
            ?? Applicant::factory()->create([
                'vacancy_id' => $vacancy->id,
                'first_name' => 'Demo',
                'last_name' => 'Applicant',
                'email' => 'applicant@example.com',
                'application_date' => now()->toDateString(),
                'status' => 'applied',
            ]);

        $evaluationPeriod = EvaluationPeriod::query()->where('name', 'Demo '.now()->year)->first()
            ?? EvaluationPeriod::factory()->create([
                'name' => 'Demo '.now()->year,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => 'active',
            ]);
        PerformanceCommitment::firstOrCreate(
            ['employee_id' => $employee->id, 'evaluation_period_id' => $evaluationPeriod->id, 'commitment_text' => 'Provide accurate payroll support'],
            ['target_value' => '100% on-time processing', 'accomplishments' => null]
        );

        Announcement::firstOrCreate(
            ['title' => 'Welcome to the HRIS'],
            [
                'content' => 'This is a sample announcement. Replace it with current office information.',
                'posted_by' => $admin->id,
                'audience' => 'all',
                'is_published' => false,
            ]
        );

        DB::table('plantilla_items')->updateOrInsert(
            ['item_number' => 'DEMO-2026-HR-001'],
            [
                'department_id' => $hrDepartment->id,
                'position_id' => $hrPosition->id,
                'allocated_count' => 1,
                'filled_count' => 1,
                'status' => 'active',
                'fiscal_year' => now()->year,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
