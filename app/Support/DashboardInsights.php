<?php

namespace App\Support;

use App\Http\Controllers\Hris\AttendanceCorrectionController;
use App\Http\Controllers\Hris\LeaveReviewController;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LoginActivity;
use App\Models\Payroll;
use App\Models\PrivacyIncident;
use App\Models\PrivacyRequest;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DashboardInsights
{
    private const COLORS = ['#69b518', '#a6eb20', '#66816b', '#94a89a', '#d8e6d3', '#c6cfc3'];

    /** @return array<string, mixed> */
    public function for(User $user, int $months): array
    {
        $start = now('Asia/Manila')->startOfMonth()->subMonths($months - 1);
        $end = now('Asia/Manila')->endOfMonth();
        $gate = Gate::forUser($user);
        $role = $user->role?->name;

        $report = match (true) {
            $role === 'Employee', $role === 'Mayor', $gate->allows('view-hris-resource', 'employees') => $this->personnel($user, $start, $end, $months),
            $gate->allows('view-hris-resource', 'payrolls') => $this->payroll($start, $end, $months),
            $role === 'MIS Administrator' => $this->accounts($start, $end, $months),
            $gate->allows('view-hris-resource', 'privacy-requests') => $this->privacy($start, $end, $months),
            default => $this->emptyReport($start, $months),
        };

        return [
            'period' => ['months' => $months, 'start' => $start->toDateString(), 'end' => $end->toDateString(), 'label' => $start->format('M j, Y').' - '.$end->format('M j, Y')],
            ...$report,
        ];
    }

    /** @return array<string, mixed> */
    private function personnel(User $user, CarbonInterface $start, CarbonInterface $end, int $months): array
    {
        $role = $user->role?->name;
        $employee = $user->employee;
        $linked = $employee && (int) $employee->user_id === $user->id;
        $employees = Employee::query();
        if ($role === 'Employee') {
            $employees->whereKey($linked ? $employee->id : 0);
        } elseif ($role === 'Department Head') {
            $employees->where('department_id', $linked ? ($employee->department_id ?? 0) : 0);
        }
        $ids = (clone $employees)->select('id');
        $attendance = AttendanceRecord::query()->whereIn('employee_id', clone $ids);
        $leaves = LeaveApplication::query()->whereIn('employee_id', clone $ids);
        $scope = match ($role) {
            'Employee' => 'Your personal records',
            'Department Head' => $linked ? ($employee->department->name ?? 'Your department') : 'Your department',
            default => 'Municipal workforce',
        };
        $canAttendance = $role === 'Employee' || Gate::forUser($user)->allows('view-hris-resource', 'attendance');
        $canLeave = $role === 'Employee' || Gate::forUser($user)->allows('view-hris-resource', 'leave-applications');
        $tasks = [];
        $rows = [];

        if ($canAttendance) {
            $bars = [
                'title' => 'Attendance overview', 'description' => 'Recorded daily time entries by month.', 'currency' => false,
                'series' => $this->series(['Present', 'Late', 'Absent / incomplete']),
                'data' => $this->months($start, $months, [
                    'primary' => $this->monthly((clone $attendance)->where('status', 'present')->toBase(), 'attendance_date', $start, $end),
                    'secondary' => $this->monthly((clone $attendance)->where('status', 'late')->toBase(), 'attendance_date', $start, $end),
                    'tertiary' => $this->monthly((clone $attendance)->whereIn('status', ['absent', 'incomplete'])->toBase(), 'attendance_date', $start, $end),
                ]),
            ];
        } else {
            $bars = [
                'title' => 'Workforce arrivals', 'description' => 'Employees grouped by date hired.', 'currency' => false,
                'series' => $this->series(['Employees hired']),
                'data' => $this->months($start, $months, ['primary' => $this->monthly((clone $employees)->toBase(), 'date_hired', $start, $end)]),
            ];
        }

        $distribution = $role === 'Employee'
            ? ['title' => 'Your attendance', 'description' => 'Attendance status in the selected period.', 'unit' => 'records', 'data' => $this->distribution((clone $attendance)->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])->toBase(), 'status')]
            : ['title' => 'Workforce status', 'description' => 'Current employee status in your workspace.', 'unit' => 'employees', 'data' => $this->distribution((clone $employees)->toBase(), 'employment_status')];

        if ($canLeave) {
            $href = $role === 'Employee' ? '/hris/portal/my-leave-requests' : '/hris/leave-applications';
            $rows = (clone $leaves)->with(['employee' => fn ($query) => $query->addSelect(['avatar' => Employee::accountAvatarQuery()]), 'leaveType'])
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->latest('start_date')->latest('id')->limit(25)->get()
                ->map(fn (LeaveApplication $row) => [
                    'id' => 'leave-'.$row->id, 'reference' => 'LR-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
                    'avatar' => $row->employee?->avatar, 'subject' => trim(($row->employee->first_name ?? '').' '.($row->employee->last_name ?? '')) ?: 'Employee record',
                    'description' => $row->leaveType->name ?? 'Leave request', 'date' => (string) $row->start_date,
                    'status' => (string) $row->status, 'value' => (float) $row->days_requested, 'unit' => 'days', 'href' => $href,
                ])->all();
            $stage = LeaveReviewController::stage($user);
            $pending = (clone $leaves)->whereIn('status', $stage ? [$stage[0]] : ['pending', 'department_reviewed', 'hr_validated']);
            if ($stage) {
                $pending->where('employee_id', '!=', $user->employee_id ?? 0);
            }
            $tasks[] = $this->task($stage ? 'Leave requests to review' : 'Leave requests in progress', 'Open the leave workspace for the next step.', $pending->count(), $href, 'amber');
        } else {
            $rows = Department::query()->withCount('employees')->orderBy('name')->limit(25)->get()
                ->map(fn (Department $row) => [
                    'id' => 'office-'.$row->id, 'reference' => (string) $row->code, 'subject' => $row->name,
                    'description' => 'Municipal office', 'date' => null, 'status' => 'active',
                    'value' => (int) $row->employees_count, 'unit' => 'employees', 'href' => '/hris/portal/staffing',
                ])->all();
        }
        if (in_array($role, ['Employee', 'HRMO Head'], true) && AttendanceCorrectionController::available($user)) {
            $corrections = AttendanceCorrection::query()->whereIn('employee_id', clone $ids);
            $corrections->whereIn('status', AttendanceCorrectionController::OPEN_STATUSES);
            if ($role !== 'Employee') {
                $corrections->where('employee_id', '!=', $user->employee_id ?? 0);
            }
            $tasks[] = $this->task('Attendance corrections', 'Time entries waiting for a follow-up.', $corrections->count(), '/hris/attendance-corrections', 'blue');
        }
        if (Gate::forUser($user)->allows('view-hris-resource', 'employees')) {
            $incomplete = (clone $employees)->where(fn ($query) => $query->whereDoesntHave('educations')->orWhereDoesntHave('documents'))->count();
            $tasks[] = $this->task('201 files to complete', 'Check education and supporting documents.', $incomplete, '/hris/employees', 'green');
        }
        if ($role === 'Mayor') {
            $tasks[] = $this->task('Open vacancies', 'Review current municipal vacancy postings.', Vacancy::query()->where('status', 'open')->count(), '/hris/vacancies', 'green');
        }

        return [
            'scope' => $scope, 'distribution' => $distribution, 'bars' => $bars,
            'activity' => ['title' => $canLeave ? 'Leave request activity' : 'Vacancy postings', 'description' => $canLeave ? 'Requests grouped by their scheduled start month.' : 'New vacancies posted in the selected period.',
                'data' => $this->months($start, $months, ['value' => $canLeave ? $this->monthly((clone $leaves)->toBase(), 'start_date', $start, $end) : $this->monthly(Vacancy::query()->toBase(), 'created_at', $start, $end)])],
            'tasks' => $tasks,
            'records' => ['title' => $canLeave ? 'Recent leave requests' : 'Office overview', 'description' => $canLeave ? 'The latest 25 requests within your reporting period.' : 'Current headcounts for up to 25 municipal offices.', 'valueLabel' => $canLeave ? 'Days' : 'Employees', 'data' => $rows],
        ];
    }

    /** @return array<string, mixed> */
    private function payroll(CarbonInterface $start, CarbonInterface $end, int $months): array
    {
        $query = Payroll::query()->whereBetween('created_at', [$start, $end]);

        return [
            'scope' => 'Payroll workspace',
            'distribution' => ['title' => 'Payroll status', 'description' => 'Payroll records in the selected period.', 'unit' => 'payrolls', 'data' => $this->distribution((clone $query)->toBase(), 'status')],
            'bars' => ['title' => 'Payroll totals', 'description' => 'Recorded gross and net pay, in PHP.', 'currency' => true, 'series' => $this->series(['Gross pay', 'Net pay']),
                'data' => $this->months($start, $months, ['primary' => $this->monthly((clone $query)->toBase(), 'created_at', $start, $end, 'gross_pay'), 'secondary' => $this->monthly((clone $query)->toBase(), 'created_at', $start, $end, 'net_pay')])],
            'activity' => ['title' => 'Payroll activity', 'description' => 'Payroll records created each month.', 'data' => $this->months($start, $months, ['value' => $this->monthly((clone $query)->toBase(), 'created_at', $start, $end)])],
            'tasks' => [
                $this->task('Draft payrolls', 'Payroll records still in preparation.', (clone $query)->where('status', 'draft')->count(), '/hris/payrolls', 'amber'),
                $this->task('Awaiting disbursement', 'Approved payrolls awaiting payment.', (clone $query)->where('status', 'approved')->count(), '/hris/payrolls', 'green'),
                $this->task('Payroll errors', 'Entries that need a closer look.', (clone $query)->where('status', 'error')->count(), '/hris/payrolls', 'blue'),
            ],
            'records' => ['title' => 'Recent payrolls', 'description' => 'The latest 25 payroll records in this period.', 'valueLabel' => 'Net pay (PHP)', 'data' => (clone $query)->with(['employee' => fn ($query) => $query->addSelect(['avatar' => Employee::accountAvatarQuery()])])->latest('id')->limit(25)->get()->map(fn (Payroll $row) => [
                'id' => 'payroll-'.$row->id, 'reference' => 'PAY-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT), 'avatar' => $row->employee?->avatar, 'subject' => trim(($row->employee->first_name ?? '').' '.($row->employee->last_name ?? '')) ?: 'Payroll record', 'description' => 'Employee payroll', 'date' => $row->created_at?->toDateString(), 'status' => (string) $row->status, 'value' => (float) $row->net_pay, 'unit' => 'PHP', 'href' => '/hris/payrolls',
            ])->all()],
        ];
    }

    /** @return array<string, mixed> */
    private function accounts(CarbonInterface $start, CarbonInterface $end, int $months): array
    {
        $logins = LoginActivity::query();

        return [
            'scope' => 'System administration',
            'distribution' => ['title' => 'Account status', 'description' => 'Current user account availability.', 'unit' => 'accounts', 'data' => [
                ['label' => 'Active', 'value' => User::query()->where('is_active', true)->count(), 'fill' => self::COLORS[0]],
                ['label' => 'Inactive', 'value' => User::query()->where('is_active', false)->count(), 'fill' => self::COLORS[3]],
            ]],
            'bars' => ['title' => 'Sign-in activity', 'description' => 'Successful and failed sign-ins by month.', 'currency' => false, 'series' => $this->series(['Successful', 'Failed']), 'data' => $this->months($start, $months, [
                'primary' => $this->monthly((clone $logins)->where('status', 'success')->toBase(), 'login_at', $start, $end), 'secondary' => $this->monthly((clone $logins)->where('status', 'failed')->toBase(), 'login_at', $start, $end),
            ])],
            'activity' => ['title' => 'New accounts', 'description' => 'User accounts created in each month.', 'data' => $this->months($start, $months, ['value' => $this->monthly(User::query()->toBase(), 'created_at', $start, $end)])],
            'tasks' => [$this->task('Inactive accounts', 'Review assigned roles and account access.', User::query()->where('is_active', false)->count(), '/hris/roles', 'amber'), $this->task('Failed sign-ins', 'Failed attempts in the reporting period.', (clone $logins)->where('status', 'failed')->whereBetween('login_at', [$start, $end])->count(), '/hris/system-settings', 'blue')],
            'records' => ['title' => 'Recent sign-in activity', 'description' => 'The latest 25 sign-in events in this period.', 'valueLabel' => 'Events', 'data' => (clone $logins)->whereBetween('login_at', [$start, $end])->latest('login_at')->limit(25)->get()->map(fn (LoginActivity $row) => [
                'id' => 'login-'.$row->id, 'reference' => 'LOG-'.$row->id, 'subject' => 'Account sign-in', 'description' => 'Authentication event', 'date' => substr((string) $row->login_at, 0, 10), 'status' => (string) $row->status, 'value' => 1, 'unit' => 'event', 'href' => '/hris/system-settings',
            ])->all()],
        ];
    }

    /** @return array<string, mixed> */
    private function privacy(CarbonInterface $start, CarbonInterface $end, int $months): array
    {
        $query = PrivacyRequest::query();

        return [
            'scope' => 'Data protection workspace',
            'distribution' => ['title' => 'Privacy request status', 'description' => 'Requests in the selected reporting period.', 'unit' => 'requests', 'data' => $this->distribution((clone $query)->whereBetween('created_at', [$start, $end])->toBase(), 'status')],
            'bars' => ['title' => 'Privacy overview', 'description' => 'Requests and incidents recorded each month.', 'currency' => false, 'series' => $this->series(['Requests', 'Incidents']), 'data' => $this->months($start, $months, ['primary' => $this->monthly((clone $query)->toBase(), 'created_at', $start, $end), 'secondary' => $this->monthly(PrivacyIncident::query()->toBase(), 'created_at', $start, $end)])],
            'activity' => ['title' => 'Processed requests', 'description' => 'Privacy requests grouped by processing month.', 'data' => $this->months($start, $months, ['value' => $this->monthly((clone $query)->toBase(), 'processed_at', $start, $end)])],
            'tasks' => [$this->task('Pending privacy requests', 'Requests waiting to be processed.', (clone $query)->where('status', 'pending')->count(), '/hris/privacy-requests', 'amber'), $this->task('Open privacy incidents', 'Reported incidents without a resolution.', PrivacyIncident::query()->whereNull('resolved_at')->count(), '/hris/privacy-incidents', 'blue')],
            'records' => ['title' => 'Recent privacy requests', 'description' => 'The latest 25 requests in this period.', 'valueLabel' => 'Requests', 'data' => (clone $query)->whereBetween('created_at', [$start, $end])->latest('id')->limit(25)->get()->map(fn (PrivacyRequest $row) => [
                'id' => 'privacy-'.$row->id, 'reference' => 'DPO-'.$row->id, 'subject' => Str::headline((string) $row->request_type), 'description' => 'Privacy request', 'date' => $row->created_at?->toDateString(), 'status' => (string) $row->status, 'value' => 1, 'unit' => 'request', 'href' => '/hris/privacy-requests',
            ])->all()],
        ];
    }

    /** @return array<string, mixed> */
    private function emptyReport(CarbonInterface $start, int $months): array
    {
        return ['scope' => 'Your workspace', 'distribution' => ['title' => 'Workspace overview', 'description' => 'Data will appear when your role is assigned.', 'unit' => 'records', 'data' => []], 'bars' => ['title' => 'Monthly overview', 'description' => 'Records available to your role.', 'currency' => false, 'series' => $this->series(['Records']), 'data' => $this->months($start, $months, ['primary' => []])], 'activity' => ['title' => 'Workspace activity', 'description' => 'Activity available to your role.', 'data' => $this->months($start, $months, ['value' => []])], 'tasks' => [], 'records' => ['title' => 'Recent records', 'description' => 'Records available to your role.', 'valueLabel' => 'Records', 'data' => []]];
    }

    /**
     * @param  literal-string  $date
     * @param  literal-string|null  $sum
     * @return array<string, float>
     */
    private function monthly(Builder $query, string $date, CarbonInterface $start, CarbonInterface $end, ?string $sum = null): array
    {
        $period = "SUBSTR({$date}, 1, 7)";
        $aggregate = $sum ? "SUM({$sum})" : 'COUNT(*)';

        return $query->whereBetween($date, [$start->toDateString(), $end->toDateTimeString()])->selectRaw("{$period} AS period, {$aggregate} AS aggregate")->groupByRaw($period)->orderBy('period')->get()->mapWithKeys(fn ($row) => [(string) $row->period => (float) $row->aggregate])->all();
    }

    /** @return list<array{label: string, value: int, fill: string}> */
    private function distribution(Builder $query, string $field): array
    {
        return array_values($query->select($field)->selectRaw('COUNT(*) AS total')->groupBy($field)->orderByDesc('total')->orderBy($field)->get()->values()->map(fn ($row, int $index) => ['label' => Str::headline((string) ($row->{$field} ?? 'Unspecified')), 'value' => (int) $row->total, 'fill' => self::COLORS[$index % count(self::COLORS)]])->all());
    }

    /** @param array<string, array<string, float>> $series
     * @return list<array<string, float|string>>
     */
    private function months(CarbonInterface $start, int $count, array $series): array
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $date = $start->copy()->addMonths($i);
            $row = ['month' => $date->format('M'), 'period' => $date->format('Y-m')];
            foreach ($series as $key => $values) {
                $row[$key] = $values[$date->format('Y-m')] ?? 0.0;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @param list<string> $labels
     * @return list<array{key: string, label: string, color: string}>
     */
    private function series(array $labels): array
    {
        $keys = ['primary', 'secondary', 'tertiary'];

        return array_map(fn (string $label, int $index) => ['key' => $keys[$index], 'label' => $label, 'color' => self::COLORS[$index]], $labels, array_keys($labels));
    }

    /** @return array{title: string, description: string, count: int, href: string, tone: string} */
    private function task(string $title, string $description, int $count, string $href, string $tone): array
    {
        return compact('title', 'description', 'count', 'href', 'tone');
    }
}
