<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use App\Models\LoginActivity;
use App\Models\Payroll;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PerformanceReview;
use App\Models\PrivacyIncident;
use App\Models\PrivacyRequest;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\DashboardInsights;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardInsights $insights): Response
    {
        $validated = $request->validate(['months' => ['sometimes', 'integer', 'in:3,6,12']]);
        $months = (int) ($validated['months'] ?? 6);
        $user = $request->user();
        $role = $user->role?->name;
        $employee = $user->employee;
        if ($employee && (int) $employee->user_id !== $user->id) {
            $employee = null;
        }
        $departmentId = $employee?->department_id;
        $departmentEmployees = Employee::query()->where('department_id', $departmentId ?? 0);
        $departmentAttendanceIssues = $departmentId
            ? AttendanceRecord::query()->whereHas('employee', fn ($query) => $query->where('department_id', $departmentId))->whereIn('status', ['absent', 'late', 'incomplete'])->count()
            : 0;
        $departmentPendingLeaves = $departmentId
            ? LeaveApplication::query()->whereHas('employee', fn ($query) => $query->where('department_id', $departmentId))->where('status', 'pending')->count()
            : 0;
        $departmentReviews = $departmentId
            ? PerformanceReview::query()->whereHas('employee', fn ($query) => $query->where('department_id', $departmentId))->count()
            : 0;

        $cards = match ($role) {
            'Employee' => [
                $this->card('Attendance this month', $employee ? AttendanceRecord::query()->where('employee_id', $employee->id)->whereBetween('attendance_date', [now()->startOfMonth(), now()->endOfMonth()])->count() : 0),
                $this->card('Available leave days', $employee ? LeaveCredit::query()->where('employee_id', $employee->id)->sum('remaining_days') : 0),
                $this->card('Payslips', $employee ? Payslip::query()->releasedForEmployee($employee->id)->count() : 0),
                $this->card('Pending leave requests', $employee ? LeaveApplication::query()->where('employee_id', $employee->id)->where('status', 'pending')->count() : 0),
            ],
            'Department Head' => [
                $this->card('Department employees', $departmentEmployees->count()),
                $this->card('Attendance issues', $departmentAttendanceIssues),
                $this->card('Pending leave approvals', $departmentPendingLeaves),
                $this->card('Performance reviews', $departmentReviews),
            ],
            'HR Officer' => [
                $this->card('Total employees', Employee::query()->count()),
                $this->card('Incomplete 201 files', Employee::query()->whereDoesntHave('educations')->orWhereDoesntHave('documents')->count()),
                $this->card('Attendance issues', AttendanceRecord::query()->whereIn('status', ['absent', 'late', 'incomplete'])->count()),
                $this->card('Pending leave requests', LeaveApplication::query()->where('status', 'pending')->count()),
            ],
            'HRMO Head', 'Administrator' => [
                $this->card('Total employees', Employee::query()->count()),
                $this->card('Departments', Department::query()->whereIn('code', array_keys(Department::PLANTILLA_OFFICES))->count()),
                $this->card('Pending HR transactions', LeaveApplication::query()->where('status', 'pending')->count()),
                $this->card('Open vacancies', Vacancy::query()->where('status', 'open')->count()),
            ],
            'Payroll Officer' => [
                $this->card('Draft payroll periods', PayrollPeriod::query()->where('status', 'draft')->count()),
                $this->card('Payrolls in preparation', Payroll::query()->where('status', 'draft')->count()),
                $this->card('Payroll errors', Payroll::query()->where('status', 'error')->count()),
                $this->card('Recorded deductions', PayrollDeduction::query()->sum('amount'), true),
            ],
            'Accountant' => [
                $this->card('Approved payrolls', Payroll::query()->where('status', 'approved')->count()),
                $this->card('Approved gross total', Payroll::query()->where('status', 'approved')->sum('gross_pay'), true),
                $this->card('Approved net total', Payroll::query()->where('status', 'approved')->sum('net_pay'), true),
                $this->card('All recorded deductions', PayrollDeduction::query()->sum('amount'), true),
            ],
            'Treasurer' => [
                $this->card('Payrolls awaiting disbursement', Payroll::query()->where('status', 'approved')->count()),
                $this->card('Net amount awaiting disbursement', Payroll::query()->where('status', 'approved')->sum('net_pay'), true),
                $this->card('Payrolls marked paid', Payroll::query()->where('status', 'paid')->count()),
            ],
            'Mayor' => [
                $this->card('Total employees', Employee::query()->count()),
                $this->card('Active employees', Employee::query()->where('employment_status', 'active')->count()),
                $this->card('Departments', Department::query()->whereIn('code', array_keys(Department::PLANTILLA_OFFICES))->count()),
                $this->card('Open vacancies', Vacancy::query()->where('status', 'open')->count()),
            ],
            'MIS Administrator' => [
                $this->card('User accounts', User::query()->count()),
                $this->card('Inactive accounts', User::query()->where('is_active', false)->count()),
                $this->card('Audit log entries', AuditLog::query()->count()),
                $this->card('Failed logins', LoginActivity::query()->where('status', 'failed')->count()),
                $this->card('System settings', SystemSetting::query()->count()),
            ],
            'DPO' => [
                $this->card('Pending privacy requests', PrivacyRequest::query()->where('status', 'pending')->count()),
                $this->card('Open privacy incidents', PrivacyIncident::query()->whereNull('resolved_at')->count()),
                $this->card('Access log entries', LoginActivity::query()->count()),
            ],
            'Auditor' => [
                $this->card('Audit log entries', AuditLog::query()->count()),
                $this->card('Payroll records', Payroll::query()->count()),
                $this->card('Payroll gross total', Payroll::query()->sum('gross_pay'), true),
                $this->card('Payroll net total', Payroll::query()->sum('net_pay'), true),
            ],
            default => [],
        };

        $announcements = Announcement::query()
            ->visible()
            ->withVisibleAt()
            ->orderByDesc('visible_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'announcements' => $announcements,
            'canManageAnnouncements' => Gate::allows('manage-hris-resource', 'announcements'),
            'role' => $role ?? 'Unassigned',
            'office' => $employee?->department?->name,
            'cards' => $cards,
            'analytics' => fn () => $insights->for($user, $months),
        ]);
    }

    /** @return array{label: string, value: string} */
    private function card(string $label, int|float|string $value, bool $currency = false): array
    {
        return [
            'label' => $label,
            'value' => $currency ? 'PHP '.number_format((float) $value, 2) : number_format((float) $value),
        ];
    }
}
