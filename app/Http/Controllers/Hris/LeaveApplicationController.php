<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApplicationController extends Controller
{
    private const REASONS = [
        'Within the Philippines',
        'Abroad',
        'In Hospital',
        'Out Patient',
        'Specify Illness',
        'Completion of Master’s Degree',
        'BAR/Board Examination Review',
        'Monetization of Leave Credits',
        'Terminal Leave',
        'Other reason',
    ];

    private const REASONS_REQUIRING_DETAILS = [
        'Abroad',
        'In Hospital',
        'Out Patient',
        'Specify Illness',
        'Other reason',
    ];

    public function create(Request $request): Response
    {
        $this->authorizeHrmoHead($request->user());

        $employees = Employee::query()
            ->whereNull('user_id')
            ->with([
                'department:id,name',
                'position:id,title',
                'plantillaItem:employee_id,department_name,position_title,actual_annual_salary',
            ])
            ->orderBy('last_name')->orderBy('first_name')->orderBy('id')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'employeeNumber' => $employee->employee_number,
                'firstName' => $employee->first_name,
                'middleName' => $employee->middle_name,
                'lastName' => $employee->last_name,
                'suffix' => $employee->suffix,
                'department' => $employee->plantillaItem->department_name ?? $employee->department?->name,
                'position' => $employee->plantillaItem->position_title ?? $employee->position?->title,
                'salary' => $employee->plantillaItem?->actual_annual_salary === null
                    ? null
                    : number_format((float) $employee->plantillaItem->actual_annual_salary / 12, 2),
            ])->values();

        return Inertia::render('hris/leave-applications/create', [
            'employees' => $employees,
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filingDate' => now('Asia/Manila')->format('F j, Y'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeHrmoHead($user);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days_requested' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::in(self::REASONS)],
            'reason_details' => ['nullable', 'string', 'max:1900'],
            'commutation' => ['required', 'boolean'],
        ]);

        if (in_array($data['reason'], self::REASONS_REQUIRING_DETAILS, true) && blank($data['reason_details'] ?? null)) {
            throw ValidationException::withMessages(['reason_details' => 'Enter the requested details.']);
        }

        $calendarDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        if ($data['days_requested'] > $calendarDays) {
            throw ValidationException::withMessages(['days_requested' => 'Requested days cannot exceed the selected date range.']);
        }

        DB::transaction(function () use ($user, $data): void {
            $employee = Employee::query()->whereKey($data['employee_id'])->lockForUpdate()->firstOrFail();
            if ($employee->user_id !== null) {
                throw ValidationException::withMessages(['employee_id' => 'Choose an employee without a linked system account.']);
            }

            $overlaps = $employee->leaveApplications()
                ->whereIn('status', ['pending', 'department_reviewed', 'hr_validated', 'approved'])
                ->where('start_date', '<=', $data['end_date'])
                ->where('end_date', '>=', $data['start_date'])
                ->exists();
            if ($overlaps) {
                throw ValidationException::withMessages(['start_date' => 'These dates overlap an existing leave request under review or already approved.']);
            }

            $reason = trim($data['reason_details'] ?? '') === ''
                ? $data['reason']
                : $data['reason'].' - '.trim($data['reason_details']);
            $leave = $employee->leaveApplications()->create([
                'leave_type_id' => $data['leave_type_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_requested' => $data['days_requested'],
                'reason' => $reason,
                'commutation' => $data['commutation'],
                'status' => 'pending',
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'leave.created_on_behalf',
                'model_type' => LeaveApplication::class,
                'model_id' => $leave->id,
                'metadata' => json_encode([
                    'employee_id' => $employee->id,
                    'status' => 'pending',
                ], JSON_THROW_ON_ERROR),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'kind' => 'create', 'message' => 'Leave application created and sent for review.']);

        return to_route('hris.resources.index', ['resource' => 'leave-applications']);
    }

    private function authorizeHrmoHead(User $user): void
    {
        abort_unless($user->is_active && $user->role?->name === 'HRMO Head', 403);
    }
}
