<?php

namespace App\Http\Controllers\Hris;

use App\Http\Requests\Hris\ImportAttendanceLogRequest;
use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Services\AttendanceLogParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
        $id = isset($filters['biometric_id']) ? (int) $filters['biometric_id'] : null;
        $unassigned = isset($filters['unassigned']);
        $batches = AttendanceImport::query()->whereNotNull('file_sha256');
        $batch = isset($filters['batch']) ? $batches->findOrFail($filters['batch']) : $batches->latest('id')->first();

        $groups = AttendancePunch::query()
            ->leftJoin('employees as owner', 'owner.biometric_id', '=', 'attendance_punches.biometric_id')
            ->when($unassigned, fn ($query) => $query->unassigned())
            ->selectRaw('attendance_punches.biometric_id, owner.employee_number, owner.first_name, owner.last_name, COUNT(*) as punch_count, MIN(punched_at) as first_punch, MAX(punched_at) as last_punch')
            ->groupBy('attendance_punches.biometric_id', 'owner.employee_number', 'owner.first_name', 'owner.last_name')
            ->orderBy('attendance_punches.biometric_id')
            ->simplePaginate(20, pageName: 'group_page')->withQueryString();

        return Inertia::render('hris/import-attendance-logs', [
            'batch' => $batch?->only(['file_name', 'total_lines', 'inserted_count', 'duplicate_count', 'invalid_count', 'clock_offset_seconds', 'invalid_lines']),
            'groups' => $groups,
            'unassigned' => $unassigned,
            'selectedId' => $id,
            'punches' => $id === null ? null : AttendancePunch::query()->where('biometric_id', $id)
                ->orderBy('punched_at')->simplePaginate(50, ['id', 'punched_at'], 'punch_page')->withQueryString(),
        ]);
    }

    public function store(ImportAttendanceLogRequest $request, AttendanceLogParser $parser): RedirectResponse
    {
        $batch = $parser->import($request->file('file'), $request->user(), $request->integer('clock_offset_seconds'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance log processed.']);

        return to_route('hris.attendance-logs.index', ['batch' => $batch->id]);
    }
}
