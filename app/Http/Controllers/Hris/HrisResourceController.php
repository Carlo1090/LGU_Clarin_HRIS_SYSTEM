<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\AttendanceRecord;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HrisResourceController extends Controller
{
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
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'options' => 'departments'],
                ['name' => 'position_id', 'label' => 'Position', 'type' => 'select', 'options' => 'positions'],
                ['name' => 'employment_status', 'label' => 'Employment status', 'type' => 'text', 'required' => true],
                ['name' => 'date_hired', 'label' => 'Date hired', 'type' => 'date'],
                ['name' => 'date_regularized', 'label' => 'Date regularized', 'type' => 'date'],
            ],
            'rules' => ['employee_number' => 'required|string|max:255', 'first_name' => 'required|string|max:255', 'middle_name' => 'nullable|string|max:255', 'last_name' => 'required|string|max:255', 'suffix' => 'nullable|string|max:255', 'gender' => 'nullable|string|max:255', 'birth_date' => 'nullable|date', 'department_id' => 'nullable|exists:departments,id', 'position_id' => 'nullable|exists:positions,id', 'employment_status' => 'required|string|max:255', 'date_hired' => 'nullable|date', 'date_regularized' => 'nullable|date'],
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
                ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'required' => true],
                ['name' => 'posted_by', 'label' => 'Posted by', 'type' => 'select', 'options' => 'users'],
                ['name' => 'audience', 'label' => 'Audience', 'type' => 'text', 'required' => true],
                ['name' => 'starts_at', 'label' => 'Starts at', 'type' => 'datetime-local'],
                ['name' => 'ends_at', 'label' => 'Ends at', 'type' => 'datetime-local'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
            'rules' => ['title' => 'required|string|max:255', 'content' => 'required|string', 'posted_by' => 'nullable|exists:users,id', 'audience' => 'required|string|max:255', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_published' => 'boolean'],
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
        $fields = $this->fieldsWithOptions($config['fields']);
        $model = $config['model'];
        $query = $model::query();

        if ($request->user()->role?->name === 'Department Head') {
            $departmentId = $request->user()->employee?->department_id;

            if (! $departmentId) {
                $query->whereRaw('1 = 0');
            } elseif ($resource === 'employees') {
                $query->where('department_id', $departmentId);
            } else {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            }
        }

        $records = $query->latest('id')->paginate(20)->withQueryString();

        return Inertia::render('hris/resources/index', [
            'resource' => $resource,
            'title' => $config['title'],
            'resources' => collect(self::RESOURCES)
                ->filter(fn (array $item, string $key) => Gate::allows('view-hris-resource', $key))
                ->map(fn (array $item, string $key) => ['key' => $key, 'title' => $item['title']])
                ->values(),
            'canManage' => Gate::allows('manage-hris-resource', $resource),
            'fields' => $fields,
            'records' => $records->through(fn (Model $record) => ['id' => $record->getKey()] + collect($config['fields'])->mapWithKeys(
                fn (array $field) => [$field['name'] => $record->getAttribute($field['name'])]
            )->all()),
            'editing' => $request->integer('edit') ?: null,
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', $resource);
        $config = $this->resourceConfig($resource);
        $model = $config['model'];
        $model::query()->create($request->validate($this->validationRules($config)));

        Inertia::flash('toast', ['type' => 'success', 'message' => $config['title'].' record created.']);

        return to_route('hris.resources.index', ['resource' => $resource]);
    }

    public function update(Request $request, string $resource, int $record): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', $resource);
        $config = $this->resourceConfig($resource);
        $model = $config['model'];
        $entry = $model::query()->findOrFail($record);
        $entry->update($request->validate($this->validationRules($config, $entry)));

        Inertia::flash('toast', ['type' => 'success', 'message' => $config['title'].' record updated.']);

        return to_route('hris.resources.index', ['resource' => $resource]);
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', $resource);
        $config = $this->resourceConfig($resource);
        $model = $config['model'];
        $model::query()->findOrFail($record)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $config['title'].' record deleted.']);

        return to_route('hris.resources.index', ['resource' => $resource]);
    }

    private function resourceConfig(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function validationRules(array $config, ?Model $record = null): array
    {
        $rules = $config['rules'];

        foreach ($config['unique'] as $field) {
            $rules[$field] = [
                ...explode('|', $rules[$field]),
                Rule::unique($record?->getTable() ?? (new $config['model'])->getTable(), $field)->ignore($record?->getKey()),
            ];
        }

        return $rules;
    }

    private function fieldsWithOptions(array $fields): array
    {
        $optionQueries = [
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::query()->orderBy('title')->pluck('title', 'id'),
            'employees' => Employee::query()->orderBy('employee_number')->get()->mapWithKeys(fn (Employee $employee) => [
                $employee->id => $employee->employee_number.' — '.$employee->first_name.' '.$employee->last_name,
            ]),
            'leave-types' => LeaveType::query()->orderBy('name')->pluck('name', 'id'),
            'payroll-periods' => PayrollPeriod::query()->orderByDesc('start_date')->pluck('code', 'id'),
            'vacancies' => Vacancy::query()->orderBy('title')->pluck('title', 'id'),
            'evaluation-periods' => EvaluationPeriod::query()->orderByDesc('start_date')->pluck('name', 'id'),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
        ];

        return array_map(function (array $field) use ($optionQueries): array {
            if (isset($field['options'])) {
                $field['options'] = $optionQueries[$field['options']]->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values();
            }

            return $field;
        }, $fields);
    }
}
