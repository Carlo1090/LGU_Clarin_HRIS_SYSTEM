<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LeaveReviewController extends Controller
{
    private const STAGES = [
        'Department Head' => ['pending', 'department_reviewed', 'Forward to HRMO'],
        'HR Officer' => ['department_reviewed', 'hr_validated', 'Validate leave'],
        'HRMO Head' => ['hr_validated', 'approved', 'Approve leave'],
    ];

    /** @return array{string, string, string}|null */
    public static function stage(User $user): ?array
    {
        return $user->is_active ? (self::STAGES[$user->role?->name] ?? null) : null;
    }

    public function __invoke(Request $request, LeaveApplication $leave): RedirectResponse
    {
        $user = $request->user();
        $stage = self::stage($user);
        abort_unless($stage !== null, 403);
        $data = $request->validate(['decision' => ['required', 'in:advance,reject']]);

        DB::transaction(function () use ($user, $stage, $data, $leave): void {
            $employee = $leave->employee()->lockForUpdate()->firstOrFail();
            $entry = LeaveApplication::query()->lockForUpdate()->findOrFail($leave->id);
            abort_if($employee->user_id === $user->id || (int) $user->employee_id === (int) $employee->id, 403);
            if ($user->role?->name === 'Department Head') {
                $head = $user->employee;
                abort_unless($head && $head->user_id === $user->id && $head->department_id && $head->department_id === $employee->department_id, 403);
            }

            [$previous, $next] = $stage;
            if ($entry->status !== $previous) {
                throw ValidationException::withMessages(['decision' => 'This request has already moved to another stage.']);
            }

            $next = $data['decision'] === 'reject' ? 'rejected' : $next;
            if (in_array($next, ['hr_validated', 'approved'], true) && $entry->leaveType?->is_paid) {
                $year = (int) substr($entry->start_date, 0, 4);
                $credits = $employee->leaveCredits()
                    ->where('leave_type_id', $entry->leave_type_id)->where('year', $year)
                    ->lockForUpdate()->get();

                if ($year !== (int) substr($entry->end_date, 0, 4) || $credits->count() !== 1 || $credits->first()->remaining_days < $entry->days_requested) {
                    throw ValidationException::withMessages(['decision' => 'Paid leave requires one sufficient balance for the requested year. Cross-year requests must be split.']);
                }

                if ($next === 'approved') {
                    $credit = $credits->first();
                    $credit->update([
                        'remaining_days' => $credit->remaining_days - $entry->days_requested,
                        'used_days' => $credit->used_days + $entry->days_requested,
                    ]);
                }
            }

            $entry->update(['status' => $next, 'approved_by' => $next === 'approved' ? $user->id : null]);
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'leave.'.$next,
                'model_type' => LeaveApplication::class,
                'model_id' => $entry->id,
                'metadata' => json_encode(['from' => $previous, 'to' => $next], JSON_THROW_ON_ERROR),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Leave decision saved.']);

        return back();
    }
}
