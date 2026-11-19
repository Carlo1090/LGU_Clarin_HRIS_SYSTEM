<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    private const PAGES = [
        'Employee' => [
            'my-profile' => 'My profile',
            'my-attendance' => 'My attendance',
            'my-leave-credits' => 'My leave credits',
            'my-leave-requests' => 'My leave requests',
            'my-payslips' => 'My payslips',
        ],
        'Mayor' => [
            'staffing' => 'Staffing overview',
            'plantilla-summary' => 'Plantilla overview',
        ],
    ];

    /** @return list<array{title: string, href: string}> */
    public static function navigation(User $user): array
    {
        if (! $user->is_active) {
            return [];
        }

        $items = [];
        foreach (self::PAGES[$user->role?->name] ?? [] as $page => $title) {
            $items[] = $page === 'my-profile'
                ? ['title' => 'My HR profile', 'href' => route('hris.my-profile', absolute: false)]
                : ['title' => $title, 'href' => route('hris.portal', $page, false)];
        }

        if (AttendanceCorrectionController::available($user)) {
            $items[] = [
                'title' => 'Attendance corrections',
                'href' => route('hris.attendance-corrections.index', [], false),
            ];
        }

        if ($user->role?->name === 'HRMO Head' && $user->requires_mfa && $user->hasEnabledTwoFactorAuthentication()) {
            $items[] = [
                'title' => 'Invite an employee',
                'href' => route('hris.invitations.create', absolute: false),
            ];
        }

        return $items;
    }

    public function submitLeave(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->is_active && $user->role?->name === 'Employee', 403);
        $employee = $user->employee;
        abort_unless($employee && $employee->user_id === $user->id, 403);

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days_requested' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $calendarDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        if ($data['days_requested'] > $calendarDays) {
            throw ValidationException::withMessages(['days_requested' => 'Requested days cannot exceed the selected date range.']);
        }

        DB::transaction(function () use ($employee, $data): void {
            $lockedEmployee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            abort_unless($lockedEmployee->user_id === $employee->user_id, 403);

            $overlaps = $lockedEmployee->leaveApplications()
                ->whereIn('status', ['pending', 'department_reviewed', 'hr_validated', 'approved'])
                ->where('start_date', '<=', $data['end_date'])
                ->where('end_date', '>=', $data['start_date'])
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['start_date' => 'These dates overlap an existing leave request under review or already approved.']);
            }

            $lockedEmployee->leaveApplications()->create($data + ['status' => 'pending']);
        });

        return to_route('hris.portal', ['page' => 'my-leave-requests']);
    }

    public function __invoke(Request $request, string $page): Response
    {
        $user = $request->user();
        $title = self::PAGES[$user->role?->name][$page] ?? null;
        abort_if($title === null || ! $user->is_active, 403);

        if ($page === 'staffing') {
            $records = Department::query()
                ->withCount('employees')
                ->withCount(['vacancies as open_vacancies_count' => fn ($query) => $query->where('status', 'open')])
                ->orderBy('name')->orderBy('id')->simplePaginate(20)
                ->through(fn (Department $department) => [
                    $department->name, $department->code,
                    $department->employees_count, $department->open_vacancies_count,
                ]);

            return Inertia::render('hris/portal', [
                'title' => $title,
                'description' => 'Municipal headcount and open vacancy postings by department.',
                'columns' => ['Department', 'Code', 'Employees', 'Open vacancy postings'],
                'records' => $records,
            ]);
        }

        if ($page === 'plantilla-summary') {
            $records = Department::query()
                ->withSum(['plantillaItems as allocated' => fn ($query) => $query->where('fiscal_year', now()->year)->where('status', 'active')], 'allocated_count')
                ->withSum(['plantillaItems as filled' => fn ($query) => $query->where('fiscal_year', now()->year)->where('status', 'active')], 'filled_count')
                ->orderBy('name')->orderBy('id')->simplePaginate(20)
                ->through(fn (Department $department) => [
                    $department->name, (int) $department->allocated, (int) $department->filled,
                    max(0, (int) $department->allocated - (int) $department->filled),
                ]);

            return Inertia::render('hris/portal', [
                'title' => $title,
                'description' => 'Active plantilla positions by department for fiscal year '.now()->year.'.',
                'columns' => ['Department', 'Allocated', 'Filled', 'Vacant'],
                'records' => $records,
            ]);
        }

        $employee = $user->employee;
        // Both links must agree before any personnel data is returned.
        if ($employee && $employee->user_id !== $user->id) {
            $employee = null;
        }

        [$description, $columns, $records] = match ($page) {
            'my-payslips' => ['Gross and net pay from your released payslips.', ['Payroll period', 'Gross pay (PHP)', 'Net pay (PHP)'],
                $employee ? Payslip::query()->releasedForEmployee($employee->id)
                    ->with('payroll.payrollPeriod')->latest('generated_at')->latest('id')->simplePaginate(20)
                    ->through(fn (Payslip $row) => [
                        $row->payroll?->payrollPeriod?->code,
                        $row->payroll ? number_format((float) $row->payroll->gross_pay, 2) : null,
                        $row->payroll ? number_format((float) $row->payroll->net_pay, 2) : null,
                    ]) : null],
            'my-profile' => ['Your employment information on file with HRMO.', ['Employee number', 'Name', 'Department', 'Position', 'Status'], null],
            'my-attendance' => ['Your daily time records.', ['Date', 'Time in', 'Time out', 'Hours', 'Status'],
                $employee?->attendanceRecords()->orderByDesc('attendance_date')->orderByDesc('id')->simplePaginate(20)
                    ->through(fn ($row) => [$row->attendance_date, $row->time_in, $row->time_out, $row->total_hours, $row->status])],
            'my-leave-credits' => ['Your recorded leave balances by year.', ['Year', 'Leave type', 'Earned', 'Used', 'Remaining'],
                $employee?->leaveCredits()->with('leaveType')->orderByDesc('year')->orderByDesc('id')->simplePaginate(20)
                    ->through(fn ($row) => [$row->year, $row->leaveType?->name, $row->earned_days, $row->used_days, $row->remaining_days])],
            'my-leave-requests' => ['Track the status of your recorded leave applications.', ['Leave type', 'From', 'To', 'Days', 'Status'],
                $employee?->leaveApplications()->with('leaveType')->latest('id')->simplePaginate(20)
                    ->through(fn ($row) => [$row->leaveType?->name, $row->start_date, $row->end_date, $row->days_requested, $row->status])],
        };

        return Inertia::render('hris/portal', [
            'title' => $title,
            'description' => $description,
            'columns' => $columns,
            'records' => $records,
            'profile' => $page === 'my-profile' && $employee ? [
                $employee->employee_number,
                trim("$employee->first_name $employee->last_name"),
                $employee->department?->name,
                $employee->position?->title,
                $employee->employment_status,
            ] : null,
            'missingEmployee' => $employee === null,
            'dtrPreviewUrl' => $page === 'my-attendance' && $employee ? route('hris.attendance.dtr', $employee, false) : null,
            'dtrDemoUrl' => $page === 'my-attendance' ? route('hris.attendance.dtr.demo', absolute: false) : null,
            'canPreviewLeaveForm' => $page === 'my-leave-requests',
            'leaveTypes' => $page === 'my-leave-requests' && $employee
                ? LeaveType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : null,
        ]);
    }
}
