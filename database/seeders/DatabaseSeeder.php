<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
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
        foreach ($roleDescriptions as $name => $description) {
            Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        foreach (Department::PLANTILLA_OFFICES as $code => $name) {
            Department::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
