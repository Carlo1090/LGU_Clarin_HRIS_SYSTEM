<?php

namespace App\Http\Controllers\Hris;

use App\Models\AttendanceClockInRule;
use App\Models\AttendancePunch;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class MonthlyAttendanceReportController
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        $filters = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
            'sort' => ['sometimes', 'in:days_attended,late_arrivals'],
            'direction' => ['sometimes', 'in:asc,desc'],
            'employee' => ['sometimes', 'integer', 'min:1'],
        ]);
        $month = $filters['month'] ?? now('Asia/Manila')->format('Y-m');
        $sort = $filters['sort'] ?? 'days_attended';
        $direction = $filters['direction'] ?? 'desc';
        $firstDay = $month.'-01';
        $lastDay = Date::parse($firstDay)->endOfMonth()->toDateString();
        $range = [$firstDay.' 00:00:00', $lastDay.' 23:59:59'];
        $daily = $this->dailyEntries($range);

        return Inertia::render('hris/monthly-attendance-report', [
            'month' => $month,
            'sort' => $sort,
            'direction' => $direction,
            'summary' => fn () => $this->summary(clone $daily, $month, $sort, $direction),
            'unlinkedIds' => fn () => AttendancePunch::query()->unassigned()
                ->whereBetween('punched_at', $range)->distinct()->count('biometric_id'),
            'detail' => fn () => isset($filters['employee'])
                ? $this->employeeDetail((int) $filters['employee'], clone $daily, $lastDay)
                : null,
        ]);
    }

    public function saveRule(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        $input = $request->validate([
            'effective_from' => ['required', 'date_format:Y-m-d', 'regex:/^[1-9][0-9]{3}-/'],
            'starts_at' => ['present', 'nullable', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'between:0,1439'],
        ]);
        $grace = $input['starts_at'] === null ? 0 : (int) $input['grace_minutes'];
        $start = $input['starts_at'] === null ? null : Date::parse('2000-01-01 '.$input['starts_at']);
        $lateAfter = $start?->addMinutes($grace)->min(Date::parse('2000-01-01 23:59:59'))->format('H:i:s');

        DB::transaction(function () use ($employee, $input, $grace, $lateAfter, $request): void {
            $rule = AttendanceClockInRule::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'effective_from' => $input['effective_from']],
                ['starts_at' => $input['starts_at'], 'grace_minutes' => $grace, 'late_after' => $lateAfter],
            );
            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'attendance.clock_in_rule_saved',
                'model_type' => AttendanceClockInRule::class,
                'model_id' => $rule->id,
                'metadata' => json_encode($rule->only(['employee_id', 'effective_from', 'starts_at', 'grace_minutes']), JSON_THROW_ON_ERROR),
            ]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clock-in rule saved.']);

        return back();
    }

    /** @param array{string, string} $range */
    private function dailyEntries(array $range): Builder
    {
        $matched = AttendancePunch::query()->whereBetween('punched_at', $range)->toBase()
            ->selectRaw(AttendancePunch::employeeIdSql().' AS employee_id, punched_at');
        $punches = DB::query()->fromSub($matched, 'matched')
            ->whereNotNull('employee_id')
            ->selectRaw('employee_id, DATE(punched_at) AS work_date, COUNT(*) AS punch_count, MIN(punched_at) AS first_punch, MAX(punched_at) AS last_punch')
            ->groupBy('employee_id')->groupByRaw('DATE(punched_at)');

        return DB::query()->fromSub($punches, 'punches')
            ->join('employees', 'employees.id', '=', 'punches.employee_id')
            ->leftJoin('attendance_clock_in_rules as schedule_rule', function (JoinClause $join): void {
                $join->on('schedule_rule.employee_id', '=', 'employees.id')
                    ->where('schedule_rule.effective_from', '=', fn (Builder $query) => $query
                        ->from('attendance_clock_in_rules as dated_rule')->selectRaw('MAX(dated_rule.effective_from)')
                        ->whereColumn('dated_rule.employee_id', 'employees.id')
                        ->whereColumn('dated_rule.effective_from', '<=', 'punches.work_date'));
            })
            ->select(['employees.id as employee_id', 'punches.work_date', 'punches.punch_count', 'punches.first_punch', 'punches.last_punch', 'schedule_rule.starts_at', 'schedule_rule.grace_minutes'])
            // shortcut: infer arrival for four-punch days, use explicit clock-in events when machine codes are verified.
            ->selectRaw('CASE WHEN punches.punch_count = ? THEN SUBSTR(punches.first_punch, 12, 8) END AS clock_in', [AttendancePunch::DTR_PUNCH_COUNT])
            ->selectRaw("CASE
                WHEN punches.punch_count <> ? OR schedule_rule.late_after IS NULL THEN 'undetermined'
                WHEN SUBSTR(punches.first_punch, 12, 8) > schedule_rule.late_after THEN 'late'
                ELSE 'on_time'
            END AS lateness", [AttendancePunch::DTR_PUNCH_COUNT]);
    }

    /**
     * @param  'asc'|'desc'  $direction
     * @return LengthAwarePaginator<array-key, array{id: mixed, employee_number: mixed, avatar: mixed, name: string, days_attended: int, late_arrivals: int, undetermined_days: int}>
     */
    private function summary(Builder $daily, string $month, string $sort, string $direction): LengthAwarePaginator
    {
        $totals = DB::query()->fromSub($daily, 'daily')->groupBy('employee_id')
            ->selectRaw("employee_id, COUNT(*) AS days_attended,
                SUM(CASE WHEN lateness = 'late' THEN 1 ELSE 0 END) AS late_arrivals,
                SUM(CASE WHEN lateness = 'undetermined' THEN 1 ELSE 0 END) AS undetermined_days");

        $summary = DB::table('employees')->leftJoinSub($totals, 'totals', 'totals.employee_id', '=', 'employees.id')
            ->select(['employees.id', 'employees.employee_number', 'employees.first_name', 'employees.last_name'])
            ->addSelect(['avatar' => Employee::accountAvatarQuery()])
            ->selectRaw('COALESCE(totals.days_attended, 0) AS days_attended, COALESCE(totals.late_arrivals, 0) AS late_arrivals, COALESCE(totals.undetermined_days, 0) AS undetermined_days')
            ->when($sort === 'late_arrivals', fn (Builder $query) => $query
                ->orderByRaw('CASE WHEN totals.days_attended = totals.undetermined_days THEN 1 ELSE 0 END'))
            ->orderBy($sort, $direction)
            ->orderBy('employees.last_name')->orderBy('employees.first_name')->orderBy('employees.id')
            ->paginate(20)->appends(compact('month', 'sort', 'direction'));

        return $summary->setCollection($summary->getCollection()->map(fn (stdClass $row) => [
            'id' => $row->id,
            'employee_number' => $row->employee_number,
            'avatar' => $row->avatar,
            'name' => "{$row->last_name}, {$row->first_name}",
            'days_attended' => (int) $row->days_attended,
            'late_arrivals' => (int) $row->late_arrivals,
            'undetermined_days' => (int) $row->undetermined_days,
        ]));
    }

    /** @return array{id: int, name: string, has_biometric_id: bool, rule: AttendanceClockInRule|null, days: Collection<int, stdClass>} */
    private function employeeDetail(int $id, Builder $daily, string $lastDay): array
    {
        $employee = Employee::query()->findOrFail($id, ['id', 'first_name', 'last_name', 'biometric_id']);
        $rule = AttendanceClockInRule::query()->where('employee_id', $id)->where('effective_from', '<=', $lastDay)
            ->latest('effective_from')->first(['effective_from', 'starts_at', 'grace_minutes']);

        return [
            'id' => $employee->id,
            'name' => "{$employee->last_name}, {$employee->first_name}",
            'has_biometric_id' => $employee->biometric_id !== null,
            'rule' => $rule,
            'days' => $daily->where('employees.id', $id)->orderBy('punches.work_date')->get(),
        ];
    }
}
