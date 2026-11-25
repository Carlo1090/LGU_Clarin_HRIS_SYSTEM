<?php

namespace App\Http\Controllers\Hris;

use App\Http\Requests\Hris\ImportAttendanceLogRequest;
use App\Jobs\ImportAttendanceLogJob;
use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceLogImportController
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        return Inertia::render('hris/import-attendance-logs', $this->pageData($request));
    }

    public function store(ImportAttendanceLogRequest $request): Response|RedirectResponse
    {
        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());
        if ($hash === false) {
            throw ValidationException::withMessages(['file' => 'The log file could not be read.']);
        }
        $storedPath = $file->storeAs(
            'attendance-imports',
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            'local',
        );
        if ($storedPath === false) {
            throw ValidationException::withMessages(['file' => 'The log file could not be stored for processing.']);
        }
        $batch = AttendanceImport::query()->create([
            'file_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'file_sha256' => $hash,
            'uploaded_by' => $request->user()->id,
            'status' => 'processing',
            'imported_at' => now(config('attendance.timezone', 'Asia/Manila')),
        ]);

        ImportAttendanceLogJob::dispatch(
            $batch->id,
            $request->user()->id,
            $storedPath,
            $file->getClientOriginalName(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance log import started.']);

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

        Inertia::flash('toast', ['type' => 'success', 'kind' => 'delete', 'message' => 'Attendance import removed.']);

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
        AttendanceImport::query()
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subHour())
            ->update([
                'status' => 'failed',
                'error_message' => 'Import processing stopped unexpectedly. Please retry the file.',
            ]);
        $batches = AttendanceImport::query();
        $batch = isset($filters['batch']) ? $batches->findOrFail($filters['batch']) : $batches->latest('id')->first();

        $punchQuery = AttendancePunch::query()
            ->when($unassigned, fn ($query) => $query->unassigned())
            ->when($assigned, fn ($query) => $query->whereNot(fn ($unlinked) => $unlinked->unassigned()))
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
        $ownerSql = AttendancePunch::employeeIdSql();
        $stats = $ids->isEmpty()
            ? collect()
            : (clone $punchQuery)
                ->whereIn('machine_user_id', $ids)
                ->selectRaw('machine_user_id, COUNT(*) as punch_count, MIN(punched_at) as first_punch, MAX(punched_at) as last_punch')
                ->selectRaw('MIN('.$ownerSql.') as owner_id, COUNT(DISTINCT '.$ownerSql.') as owner_count, COUNT(*) - COUNT('.$ownerSql.') as unlinked_count')
                ->groupBy('machine_user_id')
                ->get()
                ->keyBy('machine_user_id');
        $employees = $ids->isEmpty() || $unassigned
            ? collect()
            : Employee::query()
                ->whereKey($stats->pluck('owner_id')->filter()->values()->all())
                ->select(['id', 'user_id', 'employee_number', 'first_name', 'last_name'])
                ->addSelect(['avatar' => Employee::accountAvatarQuery()])
                ->get()
                ->keyBy('id');

        $groups = $groupIds->through(function (object $row) use ($stats, $employees): array {
            $group = $stats->get((int) $row->machine_user_id);
            $multipleOwners = (int) data_get($group, 'owner_count', 0) > 1;
            $employee = $multipleOwners ? null : $employees->get((int) data_get($group, 'owner_id'));

            return [
                'biometric_id' => (int) $row->machine_user_id,
                'employee_number' => $employee?->employee_number,
                'first_name' => $employee?->first_name,
                'last_name' => $employee?->last_name,
                'avatar' => $employee?->avatar,
                'multiple_owners' => $multipleOwners,
                'unlinked_count' => (int) data_get($group, 'unlinked_count', 0),
                'punch_count' => (int) data_get($group, 'punch_count', 0),
                'first_punch' => data_get($group, 'first_punch'),
                'last_punch' => data_get($group, 'last_punch'),
            ];
        });

        return [
            'batch' => $batch ? $batch->only(['id', 'file_name', 'status', 'error_message', 'total_lines', 'inserted_count', 'duplicate_count', 'invalid_count', 'recompute_summary'])
                + ['invalid_lines' => $batch->invalid_lines ?? []] : null,
            'groups' => $groups,
            'canLink' => in_array($request->user()->role?->name, ['HR Officer', 'HRMO Head'], true),
            'unassigned' => $unassigned,
            'assigned' => $assigned,
            'month' => $month,
            'selectedId' => $id,
            'preview' => $preview,
            'timezone' => config('attendance.timezone', 'Asia/Manila'),
            'punches' => $id === null ? null : $punchQuery->where('machine_user_id', $id)
                ->orderBy('punched_at')->simplePaginate(50, [
                    'id', 'punched_at', 'device_id', 'punch_state', 'raw_status', 'verify_mode', 'work_code',
                ], 'punch_page')->withQueryString(),
        ];
    }
}
