<?php

namespace App\Http\Controllers\Hris;

use App\Http\Requests\Hris\ImportAttendanceLogRequest;
use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\AttendanceLogParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceLogImportController
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        return Inertia::render('hris/import-attendance-logs', $this->pageData($request));
    }

    public function store(ImportAttendanceLogRequest $request, AttendanceLogParser $parser): Response|RedirectResponse
    {
        if (! $request->boolean('confirm')) {
            return Inertia::render('hris/import-attendance-logs', $this->pageData(
                $request,
                $parser->preview($request->file('file')),
            ));
        }

        $batch = $parser->import($request->file('file'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance log imported.']);

        return to_route('hris.attendance-logs.index', ['batch' => $batch->id]);
    }

    public function destroy(Request $request, AttendanceImport $batch): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        DB::transaction(function () use ($request, $batch): void {
            AttendancePunch::query()
                ->where(function ($query) use ($batch) {
                    $query->where('attendance_import_id', $batch->id)
                        ->orWhere('import_batch_id', $batch->id);
                })
                ->delete();

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'attendance.logs_deleted',
                'model_type' => AttendanceImport::class,
                'model_id' => $batch->id,
                'metadata' => json_encode([
                    'file_name' => $batch->file_name,
                    'total_lines' => $batch->total_lines,
                ], JSON_THROW_ON_ERROR),
            ]);

            $batch->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imported attendance logs deleted.']);

        return to_route('hris.attendance-logs.index');
    }

    /**
     * @param  array<string, mixed>|null  $preview
     * @return array<string, mixed>
     */
    private function pageData(Request $request, ?array $preview = null): array
    {
        $filters = $request->validate([
            'batch' => ['sometimes', 'integer', 'min:1'],
            'biometric_id' => ['sometimes', 'integer', 'between:0,4294967295'],
            'unassigned' => ['sometimes', 'in:1'],
            'assigned' => ['sometimes', 'in:1'],
            'month' => ['sometimes', 'required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
        ]);
        $id = isset($filters['biometric_id']) ? (int) $filters['biometric_id'] : null;
        $unassigned = isset($filters['unassigned']);
        $assigned = isset($filters['assigned']);
        $month = $filters['month'] ?? null;
        $batches = AttendanceImport::query();
        $batch = isset($filters['batch']) ? $batches->findOrFail($filters['batch']) : $batches->latest('id')->first();

        $punchQuery = AttendancePunch::query()
            ->when($unassigned, fn ($query) => $query->unassigned())
            ->when($assigned, fn ($query) => $query->whereHas('employee'))
            ->when($month !== null, fn ($query) => $query->whereBetween('punched_at', [
                $month.'-01 00:00:00', Date::parse($month.'-01')->endOfMonth()->toDateString().' 23:59:59',
            ]));
        $groupIds = (clone $punchQuery)
            ->select('machine_user_id')
            ->distinct()
            ->orderBy('machine_user_id')
            ->simplePaginate(20, ['machine_user_id'], 'group_page')
            ->withQueryString();

        $ids = $groupIds->getCollection()->pluck('machine_user_id')->filter()->unique()->values();
        $stats = $ids->isEmpty()
            ? collect()
            : (clone $punchQuery)
                ->whereIn('machine_user_id', $ids)
                ->selectRaw('machine_user_id, COUNT(*) as punch_count, MIN(punched_at) as first_punch, MAX(punched_at) as last_punch')
                ->groupBy('machine_user_id')
                ->get()
                ->keyBy('machine_user_id');
        $employees = $ids->isEmpty()
            ? collect()
            : Employee::query()
                ->whereIn('biometric_id', $ids)
                ->select(['id', 'user_id', 'employee_number', 'first_name', 'last_name', 'biometric_id'])
                ->addSelect(['avatar' => Employee::accountAvatarQuery()])
                ->get()
                ->keyBy('biometric_id');

        $groups = $groupIds->through(fn (object $row) => [
            'biometric_id' => (int) $row->machine_user_id,
            'employee_number' => $employees->get((int) $row->machine_user_id)?->employee_number,
            'first_name' => $employees->get((int) $row->machine_user_id)?->first_name,
            'last_name' => $employees->get((int) $row->machine_user_id)?->last_name,
            'avatar' => $employees->get((int) $row->machine_user_id)?->avatar,
            'punch_count' => (int) ($stats->get((int) $row->machine_user_id)?->punch_count ?? 0),
            'first_punch' => $stats->get((int) $row->machine_user_id)?->first_punch,
            'last_punch' => $stats->get((int) $row->machine_user_id)?->last_punch,
        ]);

        return [
            'batch' => $batch ? $batch->only(['id', 'file_name', 'total_lines', 'inserted_count', 'duplicate_count', 'invalid_count'])
                + ['invalid_lines' => $batch->invalid_lines ?? []] : null,
            'groups' => $groups,
            'unassigned' => $unassigned,
            'assigned' => $assigned,
            'month' => $month,
            'selectedId' => $id,
            'preview' => $preview,
            'timezone' => config('attendance.timezone', 'Asia/Manila'),
            'punches' => $id === null ? null : $punchQuery->where('machine_user_id', $id)
                ->orderBy('punched_at')->simplePaginate(50, [
                    'id', 'punched_at', 'device_id', 'punch_state', 'verify_mode', 'work_code', 'derived_slot', 'is_duplicate', 'flags',
                ], 'punch_page')->withQueryString(),
        ];
    }
}
