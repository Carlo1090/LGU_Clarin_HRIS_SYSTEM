<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\LoginActivity;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\PrivacyIncident;
use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Dashboard demo data can only be seeded in the local environment.');

            return;
        }

        DB::transaction(function (): void {
            $this->call(DemoPortalAccountSeeder::class);
            $offices = [];
            foreach (['DEMO-HR' => 'Demo Human Resources', 'DEMO-FIN' => 'Demo Finance', 'DEMO-OPS' => 'Demo Operations'] as $code => $name) {
                $office = Department::firstOrCreate(['code' => $code], ['name' => $name]);
                $position = Position::firstOrCreate(['code' => $code.'-STAFF'], [
                    'department_id' => $office->id, 'title' => 'Demo Staff', 'job_grade' => 'SG-10', 'is_active' => true,
                ]);
                $offices[] = [$office, $position];
            }
            $leaveType = LeaveType::firstOrCreate(['code' => 'DEMO-LV'], [
                'name' => 'Demo Vacation Leave', 'default_days' => 15, 'is_paid' => true, 'is_active' => true,
            ]);
            $names = [
                ['Ava', 'Reyes'], ['Miguel', 'Santos'], ['Isabel', 'Cruz'], ['Ethan', 'Dela Pena'],
                ['Sofia', 'Ramos'], ['Lucas', 'Garcia'], ['Mia', 'Flores'], ['Noah', 'Torres'],
                ['Aria', 'Mendoza'], ['Liam', 'Lopez'], ['Ella', 'Castillo'], ['Mateo', 'Gonzales'],
            ];
            $employees = [];
            foreach ($names as $index => [$first, $last]) {
                [$office, $position] = $offices[$index % count($offices)];
                $number = 'DEMO-DASH-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                $employee = Employee::where('employee_number', $number)->first();
                if (! $employee) {
                    $employee = Employee::factory()->create([
                        'employee_number' => $number, 'first_name' => $first, 'last_name' => $last,
                        'department_id' => $office->id, 'position_id' => $position->id,
                        'employment_status' => $index === 11 ? 'inactive' : 'active',
                    ]);
                }
                $employees[] = $employee;
                foreach (range(0, 5) as $offset) {
                    $month = now('Asia/Manila')->startOfMonth()->subMonths($offset);
                    foreach (range(1, 5 + (($index + $offset) % 5)) as $day) {
                        $date = $month->addDays($day)->toDateString();
                        $status = ['present', 'present', 'present', 'late', 'incomplete'][($index + $day + $offset) % 5];
                        AttendanceRecord::firstOrCreate(
                            ['employee_id' => $employee->id, 'attendance_date' => $date],
                            ['status' => $status, 'source' => 'manual', 'total_hours' => $status === 'incomplete' ? 4 : 8, 'overtime_hours' => 0],
                        );
                    }
                    if (($index + $offset) % 2 === 0) {
                        $start = $month->addDays(12 + ($index % 8));
                        LeaveApplication::firstOrCreate(
                            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'start_date' => $start->toDateString()],
                            ['end_date' => $start->addDay()->toDateString(), 'days_requested' => 2, 'reason' => 'Demo leave request', 'status' => ['pending', 'department_reviewed', 'approved'][($index + $offset) % 3]],
                        );
                    }
                }
            }
            foreach (['demo-employee@example.com' => 0, 'department-head@example.com' => 3] as $email => $index) {
                $user = User::where('email', $email)->first();
                $employee = $employees[$index];
                if ($user && ! $user->employee_id && ! $employee->user_id) {
                    $employee->update(['user_id' => $user->id]);
                    $user->forceFill(['employee_id' => $employee->id])->save();
                }
            }
            foreach (range(0, 5) as $offset) {
                $month = now('Asia/Manila')->startOfMonth()->subMonths($offset);
                $period = PayrollPeriod::firstOrCreate(['code' => 'DEMO-'.$month->format('Y-m')], [
                    'start_date' => $month->toDateString(), 'end_date' => $month->endOfMonth()->toDateString(), 'status' => 'draft',
                ]);
                foreach (array_slice($employees, 0, 6) as $index => $employee) {
                    if (! Payroll::where('employee_id', $employee->id)->where('payroll_period_id', $period->id)->exists()) {
                        Payroll::factory()->create([
                            'employee_id' => $employee->id, 'payroll_period_id' => $period->id,
                            'gross_pay' => 26000 + ($index * 2500), 'net_pay' => 23000 + ($index * 2200),
                            'status' => ['draft', 'approved', 'paid'][$index % 3], 'created_at' => $month->addDays(10),
                        ]);
                    }
                }
                $demoUser = User::where('email', 'mis@example.com')->first();
                if ($demoUser) {
                    foreach (['success', 'failed'] as $status) {
                        LoginActivity::firstOrCreate(['session_id' => 'demo-dashboard-'.$month->format('Y-m').'-'.$status], [
                            'user_id' => $demoUser->id, 'login_at' => $month->addDays(8), 'status' => $status, 'user_agent' => 'Demo dashboard data',
                        ]);
                    }
                }
                $employee = $employees[$offset];
                $requested = $month->addDays(5);
                if (! PrivacyRequest::where('employee_id', $employee->id)->where('request_type', 'demo_access')->where('requested_at', $requested)->exists()) {
                    $request = new PrivacyRequest(['employee_id' => $employee->id, 'request_type' => 'demo_access', 'status' => $offset % 2 ? 'processed' : 'pending', 'requested_at' => $requested, 'processed_at' => $offset % 2 ? $requested->addDays(2) : null]);
                    $request->created_at = $requested;
                    $request->save();
                }
                if (! PrivacyIncident::where('employee_id', $employee->id)->where('incident_type', 'demo_incident')->where('reported_at', $requested)->exists()) {
                    $incident = new PrivacyIncident(['employee_id' => $employee->id, 'incident_type' => 'demo_incident', 'details' => 'Fictional incident for dashboard preview.', 'severity' => 'low', 'reported_at' => $requested]);
                    $incident->created_at = $requested;
                    $incident->save();
                }
            }
        });
    }
}
