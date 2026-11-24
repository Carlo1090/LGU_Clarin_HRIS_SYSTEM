<?php

namespace App\Http\Controllers\Hris;

use App\Imports\EmployeeSpreadsheetImport;
use App\Models\Employee;
use App\Support\EmployeeImportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeImportController
{
    public function downloadTemplate(Request $request, EmployeeImportTemplate $template): BinaryFileResponse
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $path = storage_path('app/private/'.uniqid('employee-template-').'.xlsx');
        $template->download($path, $template->statuses());

        return response()->download($path, 'EMPLOYEE-BULK-IMPORT-TEMPLATE.xlsx')->deleteFileAfterSend();
    }

    public function create(Request $request, EmployeeImportTemplate $template): Response
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);

        $token = $request->query('token');
        $rows = is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)
            ? $request->session()->get('employee_import.'.$token)
            : null;

        $previewRows = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row)) {
                    unset($row['data']);
                    $previewRows[] = $row;
                }
            }
        }

        return Inertia::render('hris/import-employees', [
            'statusOptions' => $template->statuses(),
            'columns' => $template->columns(),
            'rows' => $this->withAvatars($previewRows),
            'token' => is_array($rows) ? $token : null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function withAvatars(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $photos = Employee::query()->whereIn('employee_number', array_column($rows, 'employee_number'))
            ->select('employee_number')->addSelect(['avatar' => Employee::accountAvatarQuery()])->pluck('avatar', 'employee_number');

        return array_map(fn ($row) => $row + ['avatar' => $photos->get($row['employee_number'])], $rows);
    }

    public function updateTemplate(Request $request, EmployeeImportTemplate $template): RedirectResponse
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $input = $request->validate([
            'columns' => ['required', 'array', 'min:5', 'max:40'],
            'columns.*.key' => ['nullable', 'string', 'max:100'],
            'columns.*.label' => ['required', 'string', 'max:64'],
            'status_options' => ['required', 'array', 'min:1', 'max:8'],
            'status_options.*' => ['required', 'string', 'max:30', 'not_regex:/,/'],
        ]);
        $statuses = collect($request->array('status_options'))->map(fn ($status) => trim($status))->filter()->unique()->values();
        abort_if($statuses->isEmpty() || $statuses->count() !== count($input['status_options']), 422, 'Enter distinct status names without blank values.');
        abort_if(strlen($statuses->implode(',')) > 253, 422, 'The combined status choices are too long for Excel’s dropdown.');
        $template->save($input['columns'], array_values($statuses->all()));

        return back()->with('success', 'Employee template columns and choices updated.');
    }

    public function preview(Request $request, EmployeeSpreadsheetImport $import): RedirectResponse
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $input = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);
        $rows = $import->parse($request->file('file'));
        $token = bin2hex(random_bytes(32));
        $request->session()->put('employee_import.'.$token, $rows);

        return redirect()->route('hris.employees.import', ['token' => $token]);
    }

    public function store(Request $request, EmployeeSpreadsheetImport $import): Response
    {
        abort_unless($request->user()->role?->name === 'HRMO Head', 403);
        $input = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $rows = $request->session()->pull('employee_import.'.$input['token']);
        abort_unless(is_array($rows) && count($rows), 410, 'Import preview expired. Upload the workbook again.');
        abort_if(collect($rows)->contains(fn ($row) => ! $row['valid']), 422, 'Fix all spreadsheet row errors and preview the corrected workbook.');
        $result = $import->commit(array_values($rows), $request->user());

        return Inertia::render('hris/import-employees', [
            'result' => collect($result)->except('credentials')->all(),
            'credentials' => $this->withAvatars($result['credentials']),
        ]);
    }
}
