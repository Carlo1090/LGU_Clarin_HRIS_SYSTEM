<?php

namespace App\Http\Controllers\Hris;

use App\Attendance\Models\AttendanceDaily;
use App\Attendance\Models\DepartmentSchedule;
use App\Attendance\Services\AttendanceRecomputer;
use App\Attendance\Services\ScheduleResolver;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EvaluationPeriod;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PerformanceReview;
use App\Models\PlantillaItem;
use App\Models\Position;
use App\Models\PrivacyIncident;
use App\Models\PrivacyRequest;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\HrisAccountSetup;
use App\Support\EmployeeImportTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HrisResourceController extends Controller
{
    private const APPOINTMENT_STATUS_OPTIONS = [
        'P' => 'Permanent', 'E' => 'Elected', 'T' => 'Temporary', 'S' => 'Substitute',
        'CT' => 'Coterminous', 'PA' => 'Presidential Appointee', 'FT' => 'Fixed Term',
    ];

    public function __construct(private EmployeeImportTemplate $employeeTemplate) {}

    private const PLANTILLA_FIELDS = [
        ['name' => 'organizational_unit', 'label' => 'Organizational Unit'],
        ['name' => 'item_number', 'label' => 'Item Number'],
        ['name' => 'position_title', 'label' => 'Position Title'],
        ['name' => 'salary_grade', 'label' => 'Salary Grade'],
        ['name' => 'authorized_annual_salary', 'label' => 'Authorized Annual Salary'],
        ['name' => 'actual_annual_salary', 'label' => 'Actual Annual Salary'],
        ['name' => 'step', 'label' => 'Step'],
        ['name' => 'area_code', 'label' => 'Area Code'],
        ['name' => 'area_type', 'label' => 'Area Type'],
        ['name' => 'level', 'label' => 'Level'],
        ['name' => 'last_name', 'label' => 'Last Name'],
        ['name' => 'first_name', 'label' => 'First Name'],
        ['name' => 'middle_name', 'label' => 'Middle Name'],
        ['name' => 'sex', 'label' => 'Sex'],
        ['name' => 'date_of_birth', 'label' => 'Date of Birth'],
        ['name' => 'age', 'label' => 'Age'],
        ['name' => 'tin', 'label' => 'TIN'],
        ['name' => 'date_of_original_appointment', 'label' => 'Date of Original Appointment'],
        ['name' => 'date_of_last_promotion_appointment', 'label' => 'Date of Last Promotion/Appointment'],
        ['name' => 'appointment_status', 'label' => 'Status'],
        ['name' => 'civil_service_eligibility', 'label' => 'Civil Service Eligibility'],
        ['name' => 'comment_annotation', 'label' => 'Comment/Annotation'],
        ['name' => 'pwd', 'label' => 'PWD (Y)'],
        ['name' => 'indigenous_people', 'label' => 'Indigenous People (Y)'],
        ['name' => 'solo_parent_id_number', 'label' => 'Solo Parent (ID Number)'],
        ['name' => 'abolished', 'label' => 'Abolished'],
        ['name' => 'dissolved', 'label' => 'Dissolved'],
        ['name' => 'gsis_bp_number', 'label' => 'GSIS BP Number'],
        ['name' => 'position_classification', 'label' => 'Position Classification'],
    ];

    private const RESOURCES = [
        'roles' => [
            'title' => 'Roles',
            'model' => Role::class,
            'fields' => [
                ['name' => 'name', 'label' => 'Role name', 'type' => 'text', 'required' => true],
                ['name' => 'guard_name', 'label' => 'Guard', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ],
            'rules' => ['name' => 'required|string|max:255', 'guard_name' => 'required|string|max:255', 'description' => 'nullable|string'],
            'unique' => ['name'],
        ],
        'departments' => [
            'title' => 'Departments',
            'model' => Department::class,
            'fields' => [
                ['name' => 'name', 'label' => 'Department name', 'type' => 'text', 'required' => true],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'parent_department_id', 'label' => 'Parent department', 'type' => 'select', 'options' => 'departments'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ],
            'rules' => ['name' => 'required|string|max:255', 'code' => 'required|string|max:255', 'parent_department_id' => 'nullable|exists:departments,id', 'description' => 'nullable|string'],
            'unique' => ['code'],
        ],
        'positions' => [
            'title' => 'Positions',
            'model' => Position::class,
            'fields' => [
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'required' => true, 'options' => 'departments'],
                ['name' => 'title', 'label' => 'Position title', 'type' => 'text', 'required' => true],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'job_grade', 'label' => 'Salary grade', 'type' => 'text'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'rules' => ['department_id' => 'required|exists:departments,id', 'title' => 'required|string|max:255', 'code' => 'required|string|max:255', 'job_grade' => 'nullable|string|max:255', 'is_active' => 'boolean'],
            'unique' => ['code'],
        ],
        'plantilla-items' => [
            'title' => 'Plantilla items',
            'model' => PlantillaItem::class,
            'fields' => [
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'required' => true, 'options' => 'departments'],
                ['name' => 'position_id', 'label' => 'Position', 'type' => 'select', 'required' => true, 'options' => 'positions'],
                ['name' => 'item_number', 'label' => 'Item number', 'type' => 'text'],
                ['name' => 'allocated_count', 'label' => 'Allocated positions', 'type' => 'number', 'required' => true],
                ['name' => 'filled_count', 'label' => 'Filled positions', 'type' => 'number', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
                ['name' => 'fiscal_year', 'label' => 'Fiscal year', 'type' => 'number', 'required' => true],
            ],
            'rules' => ['department_id' => 'required|exists:departments,id', 'position_id' => 'required|exists:positions,id', 'item_number' => 'nullable|string|max:255', 'allocated_count' => 'required|integer|min:0', 'filled_count' => 'required|integer|min:0', 'status' => 'required|string|max:255', 'fiscal_year' => 'required|integer|min:2000|max:2100'],
            'unique' => [],
        ],
        'employees' => [
            'title' => 'Employees',
            'model' => Employee::class,
            'fields' => [
                ['name' => 'employee_number', 'label' => 'Employee number', 'type' => 'text', 'required' => true],
                ['name' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                ['name' => 'middle_name', 'label' => 'Middle name', 'type' => 'text'],
                ['name' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                ['name' => 'suffix', 'label' => 'Suffix', 'type' => 'text'],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'text'],
                ['name' => 'birth_date', 'label' => 'Birth date', 'type' => 'date'],
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'required' => true, 'options' => 'departments'],
                ['name' => 'position_id', 'label' => 'Position', 'type' => 'select', 'options' => 'positions'],
                ['name' => 'employment_status', 'label' => 'Employment status', 'type' => 'select', 'required' => true, 'options' => 'employment-statuses'],
                ['name' => 'appointment_status', 'label' => 'Appointment status', 'type' => 'select', 'options' => 'appointment-statuses'],
                ['name' => 'date_hired', 'label' => 'Date hired', 'type' => 'date'],
                ['name' => 'date_regularized', 'label' => 'Date regularized', 'type' => 'date'],
            ],
            'rules' => ['employee_number' => 'required|string|max:255', 'first_name' => 'required|string|max:255', 'middle_name' => 'nullable|string|max:255', 'last_name' => 'required|string|max:255', 'suffix' => 'nullable|string|max:255', 'gender' => 'nullable|string|max:255', 'birth_date' => 'nullable|date', 'department_id' => 'required|exists:departments,id', 'position_id' => 'nullable|exists:positions,id', 'appointment_status' => 'nullable|in:P,E,T,S,CT,PA,FT', 'employment_status' => 'required|string|max:255', 'date_hired' => 'nullable|date', 'date_regularized' => 'nullable|date'],
            'unique' => ['employee_number'],
        ],
        'attendance' => [
            'title' => 'Attendance records',
            'model' => AttendanceRecord::class,
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'required' => true, 'options' => 'employees'],
                ['name' => 'attendance_date', 'label' => 'Attendance date', 'type' => 'date', 'required' => true],
                ['name' => 'time_in', 'label' => 'Time in', 'type' => 'datetime-local'],
                ['name' => 'time_out', 'label' => 'Time out', 'type' => 'datetime-local'],
                ['name' => 'total_hours', 'label' => 'Total hours', 'type' => 'number', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
                ['name' => 'source', 'label' => 'Source', 'type' => 'text', 'required' => true],
                ['name' => 'overtime_hours', 'label' => 'Overtime hours', 'type' => 'number', 'required' => true],
            ],
            'rules' => ['employee_id' => 'required|exists:employees,id', 'attendance_date' => 'required|date', 'time_in' => 'nullable|date', 'time_out' => 'nullable|date|after_or_equal:time_in', 'total_hours' => 'required|numeric|min:0', 'status' => 'required|string|max:255', 'source' => 'required|string|max:255', 'overtime_hours' => 'required|numeric|min:0'],
            'unique' => [],
        ],
        'leave-types' => [
            'title' => 'Leave types',
            'model' => LeaveType::class,
            'fields' => [
                ['name' => 'name', 'label' => 'Leave name', 'type' => 'text', 'required' => true],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'default_days', 'label' => 'Default days', 'type' => 'number', 'required' => true],
                ['name' => 'is_paid', 'label' => 'Paid leave', 'type' => 'checkbox'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'rules' => ['name' => 'required|string|max:255', 'code' => 'required|string|max:255', 'default_days' => 'required|integer|min:0', 'is_paid' => 'boolean', 'description' => 'nullable|string', 'is_active' => 'boolean'],
            'unique' => ['code'],
        ],
        'leave-applications' => [
            'title' => 'Leave applications',
            'model' => LeaveApplication::class,
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'required' => true, 'options' => 'employees'],
                ['name' => 'leave_type_id', 'label' => 'Leave type', 'type' => 'select', 'required' => true, 'options' => 'leave-types'],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'required' => true],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date', 'required' => true],
                ['name' => 'days_requested', 'label' => 'Days requested', 'type' => 'number', 'required' => true],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
                ['name' => 'commutation', 'label' => 'Commutation requested', 'type' => 'checkbox'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ],
            'rules' => ['employee_id' => 'required|exists:employees,id', 'leave_type_id' => 'required|exists:leave_types,id', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'days_requested' => 'required|integer|min:1', 'reason' => 'required|string', 'status' => 'required|string|max:255'],
            'unique' => [],
        ],
        'payroll-periods' => [
            'title' => 'Payroll periods',
            'model' => PayrollPeriod::class,
            'fields' => [
                ['name' => 'code', 'label' => 'Period code', 'type' => 'text', 'required' => true],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'required' => true],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ],
            'rules' => ['code' => 'required|string|max:255', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'status' => 'required|string|max:255'],
            'unique' => ['code'],
        ],
        'payrolls' => [
            'title' => 'Payrolls',
            'model' => Payroll::class,
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'required' => true, 'options' => 'employees'],
                ['name' => 'payroll_period_id', 'label' => 'Payroll period', 'type' => 'select', 'required' => true, 'options' => 'payroll-periods'],
                ['name' => 'gross_pay', 'label' => 'Gross pay', 'type' => 'number', 'required' => true],
                ['name' => 'net_pay', 'label' => 'Net pay', 'type' => 'number', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ],
            'rules' => ['employee_id' => 'required|exists:employees,id', 'payroll_period_id' => 'required|exists:payroll_periods,id', 'gross_pay' => 'required|numeric|min:0', 'net_pay' => 'required|numeric|min:0', 'status' => 'required|string|max:255'],
            'unique' => [],
        ],
        'vacancies' => [
            'title' => 'Vacancies',
            'model' => Vacancy::class,
            'fields' => [
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'options' => 'departments'],
                ['name' => 'position_id', 'label' => 'Position', 'type' => 'select', 'options' => 'positions'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['name' => 'open_positions', 'label' => 'Open positions', 'type' => 'number', 'required' => true],
                ['name' => 'closing_date', 'label' => 'Closing date', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ],
            'rules' => ['department_id' => 'nullable|exists:departments,id', 'position_id' => 'nullable|exists:positions,id', 'title' => 'required|string|max:255', 'open_positions' => 'required|integer|min:1', 'closing_date' => 'nullable|date', 'status' => 'required|string|max:255'],
            'unique' => [],
        ],
        'applicants' => [
            'title' => 'Applicants',
            'model' => Applicant::class,
            'fields' => [
                ['name' => 'vacancy_id', 'label' => 'Vacancy', 'type' => 'select', 'required' => true, 'options' => 'vacancies'],
                ['name' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                ['name' => 'middle_name', 'label' => 'Middle name', 'type' => 'text'],
                ['name' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['name' => 'application_date', 'label' => 'Application date', 'type' => 'date', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ],
            'rules' => ['vacancy_id' => 'required|exists:vacancies,id', 'first_name' => 'required|string|max:255', 'middle_name' => 'nullable|string|max:255', 'last_name' => 'required|string|max:255', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:255', 'application_date' => 'required|date', 'status' => 'required|string|max:255'],
            'unique' => [],
        ],
        'evaluation-periods' => [
            'title' => 'Evaluation periods',
            'model' => EvaluationPeriod::class,
            'fields' => [
                ['name' => 'name', 'label' => 'Period name', 'type' => 'text', 'required' => true],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'required' => true],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ],
            'rules' => ['name' => 'required|string|max:255', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'status' => 'required|string|max:255'],
            'unique' => [],
        ],
        'performance-reviews' => [
            'title' => 'Performance reviews',
            'model' => PerformanceReview::class,
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'required' => true, 'options' => 'employees'],
                ['name' => 'evaluation_period_id', 'label' => 'Evaluation period', 'type' => 'select', 'required' => true, 'options' => 'evaluation-periods'],
                ['name' => 'reviewer_id', 'label' => 'Reviewer', 'type' => 'select', 'options' => 'users'],
                ['name' => 'final_rating', 'label' => 'Final rating', 'type' => 'number', 'required' => true],
                ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ['name' => 'review_date', 'label' => 'Review date', 'type' => 'date', 'required' => true],
            ],
            'rules' => ['employee_id' => 'required|exists:employees,id', 'evaluation_period_id' => 'required|exists:evaluation_periods,id', 'reviewer_id' => 'nullable|exists:users,id', 'final_rating' => 'required|numeric|min:0|max:100', 'remarks' => 'nullable|string', 'review_date' => 'required|date'],
            'unique' => [],
        ],
        'privacy-requests' => [
            'title' => 'Privacy requests',
            'model' => PrivacyRequest::class,
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'required' => true, 'options' => 'employees'],
                ['name' => 'request_type', 'label' => 'Request type', 'type' => 'text', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
                ['name' => 'requested_at', 'label' => 'Requested at', 'type' => 'datetime-local'],
                ['name' => 'processed_at', 'label' => 'Processed at', 'type' => 'datetime-local'],
            ],
            'rules' => ['employee_id' => 'required|exists:employees,id', 'request_type' => 'required|string|max:255', 'status' => 'required|string|max:255', 'requested_at' => 'nullable|date', 'processed_at' => 'nullable|date'],
            'unique' => [],
        ],
        'privacy-incidents' => [
            'title' => 'Privacy incidents',
            'model' => PrivacyIncident::class,
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'options' => 'employees'],
                ['name' => 'incident_type', 'label' => 'Incident type', 'type' => 'text', 'required' => true],
                ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'required' => true],
                ['name' => 'severity', 'label' => 'Severity', 'type' => 'text', 'required' => true],
                ['name' => 'reported_at', 'label' => 'Reported at', 'type' => 'datetime-local'],
                ['name' => 'resolved_at', 'label' => 'Resolved at', 'type' => 'datetime-local'],
            ],
            'rules' => ['employee_id' => 'nullable|exists:employees,id', 'incident_type' => 'required|string|max:255', 'details' => 'required|string', 'severity' => 'required|string|max:255', 'reported_at' => 'nullable|date', 'resolved_at' => 'nullable|date'],
            'unique' => [],
        ],
        'announcements' => [
            'title' => 'Announcements',
            'model' => Announcement::class,
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['name' => 'content', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                ['name' => 'starts_at', 'label' => 'Starts at (Manila time)', 'type' => 'datetime-local'],
                ['name' => 'ends_at', 'label' => 'Ends at (Manila time)', 'type' => 'datetime-local'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
            'rules' => ['title' => 'required|string|max:255', 'content' => 'required|string', 'starts_at' => 'nullable|date_format:Y-m-d\TH:i', 'ends_at' => 'nullable|date_format:Y-m-d\TH:i', 'is_published' => 'boolean'],
            'unique' => [],
        ],
        'system-settings' => [
            'title' => 'System settings',
            'model' => SystemSetting::class,
            'fields' => [
                ['name' => 'key', 'label' => 'Setting key', 'type' => 'text', 'required' => true],
                ['name' => 'value', 'label' => 'Value', 'type' => 'textarea'],
                ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'rules' => ['key' => 'required|string|max:255', 'value' => 'nullable|string', 'category' => 'required|string|max:255', 'description' => 'nullable|string', 'is_active' => 'boolean'],
            'unique' => ['key'],
        ],
    ];

    /** @return list<array{title: string, href: string}> */
    public static function navigation(): array
    {
        $items = [];

        foreach (self::RESOURCES as $key => $config) {
            if (Gate::allows('view-hris-resource', $key)) {
                $items[] = ['title' => $config['title'], 'href' => '/hris/'.$key];
            }
        }

        return $items;
    }

    public function index(Request $request, string $resource): Response
    {
        Gate::authorize('view-hris-resource', $resource);
        $config = $this->resourceConfig($resource);
        $search = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'column' => ['nullable', 'string', 'max:64'],
            'assign_biometric_id' => ['nullable', 'integer', 'between:1,4294967295'],
        ]);
        $term = trim($search['q'] ?? '');
        $column = $search['column'] ?? '';
        $assignBiometricId = isset($search['assign_biometric_id']) ? (int) $search['assign_biometric_id'] : null;
        if ($resource === 'plantilla-items') {
            $records = PlantillaItem::query()->with(['department', 'employee' => fn ($query) => $query->addSelect(['avatar' => Employee::accountAvatarQuery()])])
                ->when($term !== '', function ($query) use ($term, $column) {
                    $query->where(function ($match) use ($term, $column) {
                        foreach (['position' => 'position_title', 'item' => 'item_number', 'department' => 'department_name', 'grade' => 'salary_grade'] as $key => $attribute) {
                            if ($column === '' || $column === $key) {
                                $match->orWhereLike($attribute, '%'.$term.'%');
                            }
                        }
                        if ($column === '' || $column === 'department') {
                            $match->orWhereHas('department', fn ($office) => $office->whereLike('name', '%'.$term.'%')->orWhereLike('code', '%'.$term.'%'));
                        }
                        if ($column === '' || $column === 'employee') {
                            $match->orWhereHas('employee', fn ($person) => $person->whereLike('employee_number', '%'.$term.'%')->orWhereLike('first_name', '%'.$term.'%')->orWhereLike('last_name', '%'.$term.'%'));
                        }
                    });
                })->orderBy('item_number')->orderBy('id')->paginate(20)->withQueryString()->through(
                    fn (PlantillaItem $record) => ['id' => $record->id, 'department_code' => $record->department_code ?? $record->department?->code,
                        'department_name' => $record->department_name ?? $record->department?->name,
                        'employee_number' => $record->employee?->employee_number, 'avatar' => $record->employee?->avatar]
                        + collect(self::PLANTILLA_FIELDS)->mapWithKeys(fn (array $field) => [$field['name'] => $this->plantillaValue($record, $field['name'])])->all(),
                );

            return Inertia::render('hris/plantilla-items', [
                'title' => $config['title'],
                'fields' => self::PLANTILLA_FIELDS,
                'records' => $records,
            ]);
        }
        $departmentHead = $request->user()->role?->name === 'Department Head';
        $departmentId = $request->user()->employee?->department_id;
        $canManageAttendance = in_array($resource, ['attendance', 'employees'], true) && Gate::allows('manage-hris-resource', 'attendance');
        $canLinkBiometricIds = $resource === 'employees' && $canManageAttendance;
        if ($resource === 'attendance' && $request->query('view') !== 'entries') {
            $filters = $request->validate([
                'month' => ['sometimes', 'required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
            ]);
            $month = $filters['month'] ?? now('Asia/Manila')->format('Y-m');
            $startDate = $month.'-01';
            $endDate = Date::parse($startDate)->endOfMonth()->toDateString();
            $records = Employee::query()
                ->when($term !== '', fn ($query) => $query->where(function ($match) use ($column, $term) {
                    if ($column === '' || $column === 'employee') {
                        $match->orWhereLike('first_name', '%'.$term.'%')->orWhereLike('last_name', '%'.$term.'%')->orWhereLike('employee_number', '%'.$term.'%');
                    }
                    if ($column === '' || $column === 'department') {
                        $match->orWhereHas('department', fn ($office) => $office->whereLike('name', '%'.$term.'%'));
                    }
                }))
                ->when($departmentHead, fn ($query) => $departmentId
                    ? $query->where('department_id', $departmentId)
                    : $query->whereRaw('1 = 0'))
                ->with([
                    'department:id,name',
                    'attendanceRecords' => fn ($query) => $query->whereBetween('attendance_date', [$startDate, $endDate])
                        ->latest('id')->select(['id', 'employee_id', 'attendance_date', 'time_in', 'time_out', 'status']),
                ])
                ->orderBy('last_name')->orderBy('first_name')->orderBy('id')
                ->select(['id', 'employee_number', 'first_name', 'last_name', 'suffix', 'department_id', 'biometric_id'])
                ->addSelect(['avatar' => Employee::accountAvatarQuery()])->paginate(20)
                ->withQueryString();
            $attendance = app(AttendanceRecomputer::class);
            $daily = AttendanceDaily::query()->whereIn('employee_id', $records->getCollection()->modelKeys())
                ->whereBetween('work_date', [$startDate, $endDate])->get(['employee_id', 'work_date', 'status'])
                ->groupBy('employee_id');
            $unlinkedMachineIds = $departmentHead ? 0 : AttendancePunch::query()->unassigned()
                ->whereBetween('punched_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->distinct()->count('machine_user_id');
            $records->through(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => trim("{$employee->last_name}, {$employee->first_name} {$employee->suffix}"),
                'employee_number' => $employee->employee_number,
                'department' => $employee->department?->name,
                'avatar' => $employee->avatar,
                'dtr_url' => route('hris.attendance.dtr', ['employee' => $employee, 'month' => $month], false),
            ] + $this->attendanceLogSummary($employee, $daily->get($employee->id, collect()), $attendance, $startDate, $endDate));

            return Inertia::render('hris/resources/index', [
                'resource' => $resource,
                'title' => $config['title'],
                'attendanceDirectory' => true,
                'unlinkedMachineIds' => $unlinkedMachineIds,
                'attendanceMonth' => $month,
                'canManageAttendance' => $canManageAttendance,
                'canManage' => false,
                'fields' => [
                    ['name' => 'name', 'label' => 'Employee', 'type' => 'text'],
                    ['name' => 'employee_number', 'label' => 'Employee number', 'type' => 'text'],
                    ['name' => 'department', 'label' => 'Department', 'type' => 'text'],
                    ['name' => 'attendance_summary', 'label' => 'Attendance', 'type' => 'text'],
                ],
                'records' => $records,
            ]);
        }

        $fields = $this->fieldsWithOptions($config['fields'], $departmentHead ? ($departmentId ?? 0) : null);
        $model = $config['model'];
        $query = $model::query();
        if ($resource === 'employees' && $assignBiometricId !== null) {
            $query->whereNull('biometric_id');
        }

        if ($resource === 'departments') {
            $today = now(config('attendance.timezone'))->toDateString();
            $schedules = DepartmentSchedule::query()->where('effective_from', '<=', $today)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $today))
                ->latest('effective_from')->latest('id')->get()->unique('department_id')->keyBy('department_id');
            $records = Department::query()->orderBy('name')->get()->map(fn (Department $department) => [
                'id' => $department->id, 'name' => $department->name, 'code' => $department->code,
                'parent_department_id' => $department->parent_department_id, 'description' => $department->description,
                'canManage' => in_array($department->code, array_keys(Department::PLANTILLA_OFFICES), true),
                'work_schedule' => $schedules->has($department->id) ? $this->scheduleHours($schedules->get($department->id)) : null,
            ]);
            $codes = $records->pluck('code');
            $records = $records->concat(collect(Department::PLANTILLA_OFFICES)
                ->reject(fn ($name, $code) => $codes->contains($code))
                ->map(fn ($name, $code) => ['id' => $code, 'name' => $name, 'code' => $code, 'parent_department_id' => null, 'description' => null, 'canManage' => false, 'work_schedule' => null])
                ->values())->values();

            $officeNames = $records->pluck('name', 'id');
            $fields = array_map(fn ($field) => $field['name'] === 'parent_department_id'
                ? array_merge($field, ['options' => $officeNames->map(fn ($label, $value) => compact('label', 'value'))->values()])
                : $field, $config['fields']);
            if ($term !== '') {
                $records = $records->filter(function ($row) use ($column, $term, $officeNames) {
                    $row['parent_department_id'] = $officeNames->get($row['parent_department_id']);
                    $values = $column === '' ? collect($row)->except('canManage') : collect($row)->only($column);

                    return $values->contains(fn ($value) => Str::contains((string) $value, $term, ignoreCase: true));
                })->values();
            }

            return Inertia::render('hris/resources/index', [
                'resource' => $resource, 'title' => $config['title'],
                'canManage' => false,
                'canManageSchedules' => Gate::allows('manage-attendance-schedules'),
                'records' => ['data' => $records, 'links' => []], 'editing' => null,
                'officeDirectory' => true,
                'fields' => [...$fields, ['name' => 'work_schedule', 'label' => 'Work schedule', 'type' => 'text']],
            ]);
        }

        if ($departmentHead) {

            if (! $departmentId) {
                $query->whereRaw('1 = 0');
            } elseif ($resource === 'employees') {
                $query->where('department_id', $departmentId);
            } else {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            }
        }

        $statusSummary = $resource === 'employees'
            ? (clone $query)->select('employment_status', DB::raw('count(*) as total'))->groupBy('employment_status')->orderByRaw("CASE LOWER(employment_status) WHEN 'active' THEN 1 WHEN 'on leave' THEN 2 WHEN 'inactive' THEN 3 WHEN 'terminated' THEN 4 ELSE 5 END")->orderBy('employment_status')->toBase()->get()
                ->map(fn ($status) => ['status' => $status->employment_status, 'total' => $status->total])->values()
            : [];

        if ($term !== '') {
            $terms = $column === '' ? preg_split('/\s+/u', $term) : [$term];
            foreach ($terms as $word) {
                $query->where(function ($match) use ($fields, $column, $word) {
                    foreach ($fields as $field) {
                        $name = $field['name'];
                        if ($column !== '' && $column !== $name) {
                            continue;
                        }
                        $match->orWhereLike($name, '%'.$word.'%');
                        if (isset($field['options']) && is_iterable($field['options'])) {
                            $values = collect($field['options'])->filter(fn ($option) => Str::contains((string) $option['label'], $word, ignoreCase: true))->pluck('value');
                            $match->orWhereIn($name, $values);
                        } elseif ($field['type'] === 'checkbox' && in_array(strtolower($word), ['yes', 'no'], true)) {
                            $match->orWhere($name, strtolower($word) === 'yes');
                        }
                    }
                    if ($column === '' || $column === 'id') {
                        $match->orWhereLike('id', '%'.$word.'%');
                    }
                });
            }
        }

        if ($resource === 'employees') {
            $query->with('department:id,name');
            $query->orderByRaw("CASE LOWER(employment_status) WHEN 'active' THEN 1 WHEN 'on leave' THEN 2 WHEN 'inactive' THEN 3 WHEN 'terminated' THEN 4 ELSE 5 END")
                ->orderBy('employment_status')->orderBy('last_name')->orderBy('first_name');
        }

        $canManage = $resource !== 'leave-applications' && Gate::allows('manage-hris-resource', $resource);
        $canEditEmployeeEmail = $resource === 'employees' && $canManage;
        if ($canEditEmployeeEmail) {
            $query->with('user:id,email,role_id', 'user.role:id,name');
        }

        $records = ($resource === 'employees' ? $query : $query->latest('id'))->paginate(20)->withQueryString();

        $employeeIds = $resource === 'employees'
            ? $records->getCollection()->modelKeys()
            : $records->getCollection()->pluck('employee_id')->filter();
        $avatars = Employee::query()->whereKey($employeeIds)->select('employees.id')->addSelect(['avatar' => Employee::accountAvatarQuery()])->pluck('avatar', 'id');
        $accountFields = array_column(array_filter($fields, fn ($field) => $field['account'] ?? false), 'name');
        $accountIds = collect($accountFields)->flatMap(fn ($field) => $records->getCollection()->pluck($field))->filter()->unique();
        $accountAvatars = $accountIds->isEmpty() ? collect() : User::query()->whereKey($accountIds)->pluck('avatar', 'id');
        $scheduleLabels = $resource === 'employees' ? $records->getCollection()->mapWithKeys(function (Employee $employee): array {
            $schedule = app(ScheduleResolver::class)->resolve($employee->id, now(config('attendance.timezone'))->toDateString());

            return [$employee->id => match ($schedule['schedule_source'] ?? null) {
                'override' => 'Override',
                'department' => 'From '.$employee->department?->name,
                default => null,
            }];
        }) : collect();

        return Inertia::render('hris/resources/index', [
            'resource' => $resource,
            'title' => $config['title'],
            'canManage' => $canManage,
            'leaveReviewStage' => $resource === 'leave-applications' ? LeaveReviewController::stage($request->user()) : null,
            'canCreateLeaveOnBehalf' => $resource === 'leave-applications' && $request->user()->role?->name === 'HRMO Head',
            'canImport' => $resource === 'employees' && $request->user()->role?->name === 'HRMO Head',
            'canManageAttendance' => $canManageAttendance,
            'canManageSchedules' => $resource === 'employees' && Gate::allows('manage-attendance-schedules'),
            'canLinkBiometricIds' => $canLinkBiometricIds,
            'statusSummary' => $statusSummary,
            'createdAccountCredentials' => $resource === 'employees' ? $request->session()->get('createdAccountCredentials') : null,
            'accountSetupEmailSent' => $resource === 'employees' ? $request->session()->get('accountSetupEmailSent') : null,
            'assignBiometricId' => $resource === 'employees' ? $assignBiometricId : null,
            'fields' => $fields,
            'records' => $records->through(fn (Model $record) => ['id' => $record->getKey(), 'avatar' => $avatars->get($resource === 'employees' ? $record->getKey() : $record->employee_id)]
                + collect($accountFields)->mapWithKeys(fn ($field) => [$field.'_avatar' => $accountAvatars->get($record->getAttribute($field))])->all()
                + ($canLinkBiometricIds ? $record->only(['biometric_id', 'biometric_status', 'biometric_note', 'biometric_locked']) : [])
                + ($canEditEmployeeEmail && $record->user?->role?->name === 'Employee' ? ['account_email' => $record->user->email] : [])
                + ($resource === 'employees' ? ['work_schedule' => $scheduleLabels->get($record->getKey())] : [])
                + collect($config['fields'])->mapWithKeys(fn (array $field) => [$field['name'] => $record instanceof Announcement && $field['type'] === 'datetime-local'
                    ? $record->getAttribute($field['name'])?->setTimezone(Announcement::TIMEZONE)->format('Y-m-d\TH:i')
                    : $record->getAttribute($field['name'])])->all()),
            'editing' => $request->integer('edit') ?: null,
        ]);
    }

    private function scheduleHours(DepartmentSchedule $schedule): string
    {
        return implode(' / ', [
            substr($schedule->am_in, 0, 5).'-'.substr($schedule->am_out, 0, 5),
            substr($schedule->pm_in, 0, 5).'-'.substr($schedule->pm_out, 0, 5),
        ]);
    }

    public function myProfile(Request $request): Response
    {
        $employee = $request->user()->employee;
        abort_unless($employee !== null, 404);
        $employee->load(['department', 'plantillaItem.department']);
        $item = $employee->plantillaItem;

        return Inertia::render('hris/my-profile', [
            'employee' => [
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'middle_name' => $employee->middle_name,
                'last_name' => $employee->last_name,
                'suffix' => $employee->suffix,
                'gender' => $employee->gender,
                'birth_date' => $employee->birth_date?->format('Y-m-d'),
                'date_hired' => $employee->date_hired?->format('Y-m-d'),
                'date_regularized' => $employee->date_regularized?->format('Y-m-d'),
                'age' => $employee->age,
                'tin' => $employee->tin,
                'date_of_original_appointment' => $employee->date_of_original_appointment?->format('Y-m-d'),
                'date_of_last_promotion_appointment' => $employee->date_of_last_promotion_appointment?->format('Y-m-d'),
                'appointment_status' => $employee->appointment_status,
                'employment_status' => $employee->employment_status,
                'civil_service_eligibility' => $employee->civil_service_eligibility,
                'comment_annotation' => $employee->comment_annotation,
                'pwd' => $employee->pwd,
                'indigenous_people' => $employee->indigenous_people,
                'solo_parent_id_number' => $employee->solo_parent_id_number,
                'gsis_bp_number' => $employee->gsis_bp_number,
                'department' => $item->department_name ?? $item->department->name ?? $employee->department?->name,
                'organizational_unit' => $item?->organizational_unit,
                'item_number' => $item?->item_number,
                'position_title' => $item->position_title ?? $employee->position?->title,
                'salary_grade' => $item?->salary_grade,
                'authorized_annual_salary' => $item?->authorized_annual_salary,
                'actual_annual_salary' => $item?->actual_annual_salary,
                'step' => $item?->step,
                'area_code' => $item?->area_code,
                'area_type' => $item?->area_type,
                'level' => $item?->level,
                'abolished' => $item?->abolished,
                'dissolved' => $item?->dissolved,
                'position_classification' => $item?->position_classification,
            ],
            'customFields' => collect(app(EmployeeImportTemplate::class)->columns())
                ->filter(fn ($column) => str_starts_with($column['key'], 'custom_'))
                ->map(fn ($column) => ['label' => $column['label'], 'value' => $employee->custom_fields[$column['key']] ?? null])
                ->values(),
        ]);
    }

    public function downloadPlantillaWorkbook(): BinaryFileResponse
    {
        Gate::authorize('view-hris-resource', 'plantilla-items');
        $path = storage_path('app/private/TEMPLATE_HRMO-AND-PLANTILLA-IMPORT.xlsx');
        abort_unless(is_file($path), 404, 'The Plantilla workbook has not been uploaded to storage.');

        return response()->download($path, 'TEMPLATE_HRMO-AND-PLANTILLA-IMPORT.xlsx');
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        abort_if($resource === 'leave-applications', 403);
        Gate::authorize('manage-hris-resource', $resource);
        if ($resource === 'plantilla-items') {
            DB::transaction(function () use ($request): void {
                $data = $this->validatedPlantillaData($request);
                $employee = $this->resolvePlantillaEmployee($data);
                $this->savePlantillaEmployee($employee, $request->all());
                foreach (['employee_number', ...$this->employeePlantillaFields()] as $field) {
                    unset($data[$field]);
                }
                PlantillaItem::query()->create($data + ['employee_id' => $employee?->id]);
            });
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla position record created.']);

            return to_route('hris.resources.index', ['resource' => $resource]);
        }
        if ($resource === 'employees') {
            $input = $request->validate($this->validationRules($this->resourceConfig($resource)) + [
                'account_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            ]);
            $email = filled($input['account_email'] ?? null) ? Str::lower($input['account_email']) : null;
            unset($input['account_email']);
            $temporaryPassword = Str::password(18);
            $employee = DB::transaction(function () use ($input, $request, $temporaryPassword, $email): Employee {
                $employee = Employee::query()->create($input);
                $role = Role::query()->where('name', 'Employee')->where('guard_name', 'web')->firstOrFail();
                $account = new User([
                    'name' => trim($employee->first_name.' '.$employee->last_name),
                    'email' => $email,
                    'password' => $temporaryPassword,
                ]);
                $account->forceFill([
                    'employee_id' => $employee->id,
                    'role_id' => $role->id,
                    'requires_mfa' => true,
                    'must_change_password' => true,
                ])->save();
                $employee->forceFill(['user_id' => $account->id])->save();
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'employee.account_created',
                    'model_type' => Employee::class,
                    'model_id' => $employee->id,
                    'metadata' => json_encode(['employee_number' => $employee->employee_number], JSON_THROW_ON_ERROR),
                ]);

                return $employee;
            });
            $emailSetupSent = false;
            if ($email) {
                try {
                    $emailSetupSent = HrisAccountSetup::sendTo($email) === Password::RESET_LINK_SENT;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee account created.']);

            return to_route('hris.resources.index', ['resource' => $resource])->with([
                'createdAccountCredentials' => [
                    'employee_number' => $employee->employee_number,
                    'reference_code' => $employee->referenceCode(),
                    'username' => $employee->employee_number,
                    'name' => trim($employee->first_name.' '.$employee->last_name),
                    'temporary_password' => $temporaryPassword,
                    'email' => $email,
                ],
                'accountSetupEmailSent' => $email ? (bool) $emailSetupSent : null,
            ]);
        }
        $config = $this->resourceConfig($resource);
        $model = $config['model'];
        $data = $request->validate($this->validationRules($config));
        if ($resource === 'announcements') {
            $data = $this->announcementSchedule($data) + ['posted_by' => $request->user()->id, 'audience' => 'all'];
        }
        $model::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => $config['title'].' record created.']);

        return $this->resourceIndexAfterMutation($resource);
    }

    public function update(Request $request, string $resource, int $record): RedirectResponse
    {
        abort_if($resource === 'leave-applications', 403);
        Gate::authorize('manage-hris-resource', $resource);
        if ($resource === 'plantilla-items') {
            $item = PlantillaItem::query()->findOrFail($record);
            DB::transaction(function () use ($request, $item): void {
                $data = $this->validatedPlantillaData($request, $item);
                $employee = $this->resolvePlantillaEmployee($data, $item);
                $this->savePlantillaEmployee($employee, $request->all());
                foreach (['employee_number', ...$this->employeePlantillaFields()] as $field) {
                    unset($data[$field]);
                }
                $item->update($data + ['employee_id' => $employee?->id]);
            });
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla position record updated.']);

            return to_route('hris.resources.index', ['resource' => $resource]);
        }
        $config = $this->resourceConfig($resource);
        $model = $config['model'];
        $entry = $model::query()->findOrFail($record);
        $data = $request->validate($this->validationRules($config, $entry));
        if ($entry instanceof Announcement) {
            $data = $this->announcementSchedule($data, $entry);
        }
        if ($entry instanceof Employee && $request->has('account_email')) {
            DB::transaction(function () use ($entry, $request, $data): void {
                $account = $entry->user()->lockForUpdate()->first();
                if ($account?->role?->name !== 'Employee') {
                    throw ValidationException::withMessages(['account_email' => 'Email can only be edited for a linked Employee account.']);
                }

                $email = $request->input('account_email');
                $validated = validator([
                    'account_email' => is_string($email) ? Str::lower($email) : $email,
                ], [
                    'account_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account)],
                ])->validate();

                $account->email = $validated['account_email'];
                if ($account->isDirty('email')) {
                    $account->email_verified_at = null;
                    $account->save();
                }

                $entry->update($data);
            });
        } else {
            $entry->update($data);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $config['title'].' record updated.']);

        return $this->resourceIndexAfterMutation($resource);
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        abort_if($resource === 'leave-applications', 403);
        Gate::authorize('manage-hris-resource', $resource);
        $config = $this->resourceConfig($resource);
        $model = $config['model'];
        $model::query()->findOrFail($record)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $config['title'].' record deleted.']);

        return $this->resourceIndexAfterMutation($resource);
    }

    private function announcementSchedule(array $data, ?Announcement $announcement = null): array
    {
        foreach (['starts_at', 'ends_at'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = Date::parse($data[$field], Announcement::TIMEZONE)->utc();
            }
        }

        $start = array_key_exists('starts_at', $data) ? $data['starts_at'] : $announcement?->starts_at;
        $end = array_key_exists('ends_at', $data) ? $data['ends_at'] : $announcement?->ends_at;
        if ($start && $end && $end <= $start) {
            throw ValidationException::withMessages(['ends_at' => 'The end time must be after the start time.']);
        }

        return $data;
    }

    private function resourceIndexAfterMutation(string $resource): RedirectResponse
    {
        $parameters = ['resource' => $resource];
        if ($resource === 'attendance') {
            $parameters['view'] = 'entries';
        }

        return to_route('hris.resources.index', $parameters);
    }

    /**
     * @param  Collection<int, AttendanceDaily>  $daily
     * @return array{attendance_complete_days: int, attendance_review_days: int}
     */
    private function attendanceLogSummary(Employee $employee, Collection $daily, AttendanceRecomputer $attendance, string $from, string $to): array
    {
        $complete = $review = 0;
        $manualDays = $employee->attendanceRecords->unique('attendance_date')->keyBy('attendance_date');
        foreach ($manualDays as $entry) {
            $status = str_replace([' ', '-'], '_', strtolower(trim($entry->status)));
            if (in_array($status, ['leave', 'on_leave', 'holiday', 'rest_day', 'day_off'], true)) {
                continue;
            }
            if ($entry->time_in && $entry->time_out) {
                $complete++;
            } else {
                $review++;
            }
        }

        $stored = $daily->keyBy(fn (AttendanceDaily $day): string => $day->work_date->toDateString());
        $workDates = array_unique([
            ...$attendance->recordedWorkDates($employee->id, $employee->biometric_id, $from, $to),
            ...$stored->where('status', 'absent')->keys()->all(),
        ]);
        foreach ($workDates as $date) {
            if ($manualDays->has($date)) {
                continue;
            }
            $status = $stored->get($date)?->status ?? $attendance->employeeDay($employee->id, $date)['status'];
            if ($status === 'complete') {
                $complete++;
            } elseif (in_array($status, ['incomplete', 'absent', 'no_schedule'], true)) {
                $review++;
            }
        }

        return ['attendance_complete_days' => $complete, 'attendance_review_days' => $review];
    }

    /** @return array{model: class-string<Model>, title: string, fields: list<array{name: string, label: string, type: string, required?: bool, options?: string}>, rules: array<string, string>, unique: list<string>} */
    private function resourceConfig(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    /** @return Collection<string, string> */
    private function plantillaDepartments(): Collection
    {
        return collect(Department::PLANTILLA_OFFICES)->union(
            Department::query()->whereNotIn('code', array_keys(Department::PLANTILLA_OFFICES))->orderBy('name')->pluck('name', 'code'),
        );
    }

    /** @return array<string, mixed> */
    private function validatedPlantillaData(Request $request, ?PlantillaItem $record = null): array
    {
        $departmentOptions = $this->plantillaDepartments();
        $data = $request->validate([
            'department_code' => ['required', 'string', Rule::in($departmentOptions->keys()->all())],
            'employee_number' => ['nullable', 'string', 'exists:employees,employee_number'],
            'organizational_unit' => ['required', 'string', 'max:255'],
            'item_number' => ['nullable', 'string', 'max:255', Rule::unique('plantilla_items', 'item_number')
                ->where(fn ($query) => $query->where('department_code', $request->input('department_code')))
                ->ignore($record?->id)],
            'position_title' => ['required', 'string', 'max:255'],
            'salary_grade' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'authorized_annual_salary' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'actual_annual_salary' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'step' => ['nullable', 'numeric', 'min:0', 'max:8'],
            'area_code' => ['nullable', 'string', 'max:50'],
            'area_type' => ['nullable', Rule::in(['R', 'P', 'D', 'M', 'F'])],
            'level' => ['nullable', Rule::in(['K', 'A', 'S', 'T'])],
            'last_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', Rule::in(['M', 'F'])],
            'date_of_birth' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'tin' => ['nullable', 'string', 'max:64'],
            'date_of_original_appointment' => ['nullable', 'date'],
            'date_of_last_promotion_appointment' => ['nullable', 'date'],
            'appointment_status' => ['required', Rule::in(['P', 'E', 'T', 'S', 'CT', 'PA', 'FT'])],
            'civil_service_eligibility' => ['required', 'string', 'max:255'],
            'comment_annotation' => ['nullable', 'string', 'max:10000'],
            'pwd' => ['nullable', Rule::in(['Y'])],
            'indigenous_people' => ['nullable', Rule::in(['Y'])],
            'solo_parent_id_number' => ['nullable', 'string', 'max:255'],
            'abolished' => ['nullable', 'string', 'max:255'],
            'dissolved' => ['nullable', 'string', 'max:255'],
            'gsis_bp_number' => ['nullable', 'string', 'max:100'],
            'position_classification' => ['nullable', Rule::in(['1st Level', '2nd Level', 'Executive/Managerial (appointment by authorities other than the President)', '3rd Level (appointment by the President)'])],
        ]);
        foreach ($this->employeePlantillaFields() as $field) {
            $data[$field] = $data[$field] ?? null;
        }
        $data['department_name'] = $departmentOptions[$data['department_code']];
        $data['department_id'] = Department::query()->where('code', $data['department_code'])->value('id');

        return $data;
    }

    /** @return list<string> */
    private function employeePlantillaFields(): array
    {
        return ['last_name', 'first_name', 'middle_name', 'sex', 'date_of_birth', 'age', 'tin', 'date_of_original_appointment', 'date_of_last_promotion_appointment', 'appointment_status', 'civil_service_eligibility', 'comment_annotation', 'pwd', 'indigenous_people', 'solo_parent_id_number', 'gsis_bp_number'];
    }

    /** @param array<string, mixed> $data */
    private function resolvePlantillaEmployee(array $data, ?PlantillaItem $record = null): ?Employee
    {
        $number = $data['employee_number'] ?? null;
        if (! $number) {
            return null;
        }

        $employee = Employee::query()->where('employee_number', $number)->firstOrFail();
        abort_if(PlantillaItem::query()->where('employee_id', $employee->id)->when($record, fn ($query) => $query->whereKeyNot($record->id))->exists(), 422, 'This employee is already linked to another Plantilla position.');

        return $employee;
    }

    /** @param array<string, mixed> $input */
    private function savePlantillaEmployee(?Employee $employee, array $input): void
    {
        if (! $employee) {
            return;
        }

        $employee->fill([
            'last_name' => $input['last_name'] ?? null,
            'first_name' => $input['first_name'] ?? null,
            'middle_name' => $input['middle_name'] ?? null,
            'gender' => $input['sex'] ?? null,
            'birth_date' => $input['date_of_birth'] ?? null,
            'age' => $input['age'] ?? null,
            'tin' => $input['tin'] ?? null,
            'date_of_original_appointment' => $input['date_of_original_appointment'] ?? null,
            'date_of_last_promotion_appointment' => $input['date_of_last_promotion_appointment'] ?? null,
            'appointment_status' => $input['appointment_status'] ?? null,
            'civil_service_eligibility' => $input['civil_service_eligibility'] ?? null,
            'comment_annotation' => $input['comment_annotation'] ?? null,
            'pwd' => $input['pwd'] ?? null,
            'indigenous_people' => $input['indigenous_people'] ?? null,
            'solo_parent_id_number' => $input['solo_parent_id_number'] ?? null,
            'gsis_bp_number' => $input['gsis_bp_number'] ?? null,
        ])->save();
    }

    private function plantillaValue(PlantillaItem $record, string $field): mixed
    {
        return match ($field) {
            'employee_number' => $record->employee?->employee_number,
            'last_name' => $record->employee?->last_name,
            'first_name' => $record->employee?->first_name,
            'middle_name' => $record->employee?->middle_name,
            'sex' => $record->employee?->gender,
            'date_of_birth' => $record->employee?->birth_date?->format('Y-m-d'),
            'age', 'tin', 'date_of_original_appointment', 'date_of_last_promotion_appointment', 'appointment_status', 'civil_service_eligibility', 'comment_annotation', 'pwd', 'indigenous_people', 'solo_parent_id_number', 'gsis_bp_number' => $record->employee?->getAttribute($field),
            default => $record->getAttribute($field),
        };
    }

    /**
     * @param  array{model: class-string<Model>, rules: array<string, string>, unique: list<string>}  $config
     * @return array<string, string|list<string|Unique>>
     */
    private function validationRules(array $config, ?Model $record = null): array
    {
        $rules = $config['rules'];

        if ($config['model'] === Employee::class) {
            $rules['employment_status'] = 'required|string|in:'.implode(',', $this->employeeTemplate->statuses());
            $rules['appointment_status'] = 'nullable|in:'.implode(',', array_keys(self::APPOINTMENT_STATUS_OPTIONS));
        }

        foreach ($config['unique'] as $field) {
            $rules[$field] = [
                ...explode('|', $config['rules'][$field]),
                Rule::unique($record?->getTable() ?? (new $config['model'])->getTable(), $field)->ignore($record?->getKey()),
            ];
        }

        return $rules;
    }

    /**
     * @param  list<array{name: string, label: string, type: string, required?: bool, options?: string}>  $fields
     * @return list<array<string, mixed>>
     */
    private function fieldsWithOptions(array $fields, ?int $departmentId): array
    {
        $optionQueries = [
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::query()->orderBy('title')->pluck('title', 'id'),
            'employees' => Employee::query()->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))->orderBy('employee_number')->get()->mapWithKeys(fn (Employee $employee) => [
                $employee->id => $employee->employee_number.' â€” '.$employee->first_name.' '.$employee->last_name,
            ]),
            'leave-types' => LeaveType::query()->orderBy('name')->pluck('name', 'id'),
            'payroll-periods' => PayrollPeriod::query()->orderByDesc('start_date')->pluck('code', 'id'),
            'vacancies' => Vacancy::query()->orderBy('title')->pluck('title', 'id'),
            'evaluation-periods' => EvaluationPeriod::query()->orderByDesc('start_date')->pluck('name', 'id'),
            'users' => User::query()->when($departmentId !== null, fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->where('department_id', $departmentId)))->orderBy('name')->pluck('name', 'id'),
            'employment-statuses' => collect($this->employeeTemplate->statuses())->mapWithKeys(fn (string $status) => [$status => ucfirst($status)]),
            'appointment-statuses' => collect(self::APPOINTMENT_STATUS_OPTIONS),
        ];

        return array_map(function (array $field) use ($optionQueries): array {
            if (isset($field['options'])) {
                if ($field['options'] === 'users') {
                    $field['account'] = true;
                }
                $field['options'] = $optionQueries[$field['options']]->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values();
            }

            return $field;
        }, $fields);
    }
}
