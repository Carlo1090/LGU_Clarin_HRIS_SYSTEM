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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceLogImportController
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-hris-resource', 'attendance');
        $filters = $request->validate([
            'batch' => ['sometimes', 'integer', 'min:1'],
            'biometric_id' => ['sometimes', 'integer', 'between:1,4294967295'],
            'unassigned' => ['sometimes', 'in:1'],
            'month' => ['sometimes', 'required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
        ]);
        $id = isset($filters['biometric_id']) ? (int) $filters['biometric_id'] : null;
        $unassigned = isset($filters['unassigned']);
        $month = $filters['month'] ?? null;
        $batches = AttendanceImport::query()->whereNotNull('file_sha256');
        $batch = isset($filters['batch']) ? $batches->findOrFail($filters['batch']) : $batches->latest('id')->first();

        $punchQuery = AttendancePunch::query()
            ->when($unassigned, fn ($query) => $query->unassigned())
            ->when($month !== null, fn ($query) => $query->whereBetween('punched_at', [
                $month.'-01 00:00:00', Date::parse($month.'-01')->endOfMonth()->toDateString().' 23:59:59',
            ]));
        $groups = (clone $punchQuery)
            ->leftJoin('employees as owner', 'owner.biometric_id', '=', 'attendance_punches.biometric_id')
            ->selectRaw('attendance_punches.biometric_id, owner.employee_number, owner.first_name, owner.last_name, COUNT(*) as punch_count, MIN(punched_at) as first_punch, MAX(punched_at) as last_punch')
            ->addSelect(['avatar' => Employee::accountAvatarQuery('owner')])
            ->groupBy('attendance_punches.biometric_id', 'owner.id', 'owner.user_id', 'owner.employee_number', 'owner.first_name', 'owner.last_name')
            ->orderBy('attendance_punches.biometric_id')
            ->simplePaginate(20, pageName: 'group_page')->withQueryString();

        return Inertia::render('hris/import-attendance-logs', [
            'batch' => $batch?->only(['id', 'file_name', 'total_lines', 'inserted_count', 'duplicate_count', 'invalid_count', 'clock_offset_seconds', 'invalid_lines']),
            'groups' => $groups,
            'unassigned' => $unassigned,
            'month' => $month,
            'selectedId' => $id,
            'punches' => $id === null ? null : $punchQuery->where('biometric_id', $id)
                ->orderBy('punched_at')->simplePaginate(50, ['id', 'punched_at'], 'punch_page')->withQueryString(),
        ]);
    }

    public function store(ImportAttendanceLogRequest $request, AttendanceLogParser $parser): RedirectResponse
    {
        $batch = $parser->import($request->file('file'), $request->user(), $request->integer('clock_offset_seconds'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance log processed.']);

        return to_route('hris.attendance-logs.index', ['batch' => $batch->id]);
    }

    public function destroy(Request $request, int $batch): RedirectResponse
    {
        Gate::authorize('manage-hris-resource', 'attendance');

        DB::transaction(function () use ($request, $batch): void {
            $import = AttendanceImport::query()->whereNotNull('file_sha256')->lockForUpdate()->findOrFail($batch);
            $importedIds = AttendancePunch::query()->where('attendance_import_id', $batch)->select('biometric_id');

            Employee::query()->where('biometric_status', 'verified')
                ->whereIn('biometric_id', $importedIds)
                ->whereDoesntHave('attendancePunches', fn ($punches) => $punches->where('attendance_import_id', '<>', $batch))
                ->update([
                    'biometric_status' => 'pending',
                    'biometric_note' => 'No punches remain for this ID. Import its attendance logs to verify it again.',
                    'biometric_verified_at' => null,
                    'biometric_verified_by' => null,
                ]);

            $import->delete();

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'attendance.logs_removed',
                'model_type' => AttendanceImport::class,
                'model_id' => $batch,
                'metadata' => json_encode($import->only(['file_name', 'inserted_count']), JSON_THROW_ON_ERROR),
            ]);
        }, attempts: 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance import removed.']);

        return to_route('hris.attendance-logs.index');
    }
}
