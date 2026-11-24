<?php

namespace App\Http\Controllers\Hris;

use App\Http\Requests\Hris\ImportAttendanceLogRequest;
use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\Employee;
use App\Services\AttendanceLogParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
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
        $groups = (clone $punchQuery)
            ->leftJoin('employees as owner', 'owner.biometric_id', '=', 'attendance_punches.machine_user_id')
            ->selectRaw('attendance_punches.machine_user_id as biometric_id, owner.employee_number, owner.first_name, owner.last_name, COUNT(*) as punch_count, MIN(punched_at) as first_punch, MAX(punched_at) as last_punch')
            ->addSelect(['avatar' => Employee::accountAvatarQuery('owner')])
            ->groupBy('attendance_punches.machine_user_id', 'owner.id', 'owner.user_id', 'owner.employee_number', 'owner.first_name', 'owner.last_name')
            ->orderBy('attendance_punches.machine_user_id')
            ->simplePaginate(20, pageName: 'group_page')->withQueryString();

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
