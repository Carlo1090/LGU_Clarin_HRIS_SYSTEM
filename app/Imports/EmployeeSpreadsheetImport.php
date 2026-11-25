<?php

namespace App\Imports;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeAccountCreator;
use App\Support\EmployeeImportTemplate;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

/** @phpstan-import-type ActivationSlip from EmployeeAccountCreator */
class EmployeeSpreadsheetImport
{
    public function __construct(private EmployeeImportTemplate $template, private EmployeeAccountCreator $accounts) {}

    private const FIELDS = ['employee_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'department', 'position', 'employment_status', 'date_hired', 'biometric_id'];

    private const HEADER_FIELDS = [
        'employeenumber' => 'employee_number', 'firstname' => 'first_name', 'middlename' => 'middle_name',
        'lastname' => 'last_name', 'suffix' => 'suffix', 'department' => 'department',
        'departmentname' => 'department', 'departmentcode' => 'department', 'position' => 'position',
        'positiontitle' => 'position', 'positioncode' => 'position', 'employmentstatus' => 'employment_status',
        'emailaddress' => 'email',
    ];

    /** @return list<array<string, mixed>> */
    public function parse(UploadedFile $file): array
    {
        if ($file->getClientOriginalExtension() !== 'xlsx') {
            throw ValidationException::withMessages(['file' => 'Upload the Employee Excel workbook (.xlsx).']);
        }

        $sheetRows = $this->sheetRows($file->getRealPath(), 'EMPLOYEES');
        $headers = $sheetRows[1] ?? [];
        $mappedHeaders = [];
        $columns = $this->template->columns();
        $allowedKeys = array_column($columns, 'key');
        $headerMap = collect($columns)->mapWithKeys(fn ($column) => [preg_replace('/[^a-z0-9]/', '', strtolower($column['label'])) => $column['key']])->all();
        foreach ($headers as $index => $header) {
            $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $header)));
            $field = $headerMap[$normalized] ?? (in_array(self::HEADER_FIELDS[$normalized] ?? null, $allowedKeys, true) ? self::HEADER_FIELDS[$normalized] : null);
            if ($field) {
                $mappedHeaders[$index] = $field;
            }
        }
        $requiredKeys = ['employee_number', 'first_name', 'last_name', 'department', 'employment_status'];
        if (array_diff($requiredKeys, array_values($mappedHeaders))) {
            throw ValidationException::withMessages(['file' => 'The workbook must include Employee Number, First Name, Last Name, Department, and Employment Status.']);
        }
        $customKeys = array_filter(array_column($columns, 'key'), fn ($key) => str_starts_with($key, 'custom_'));

        $departments = Department::query()->get();
        $positions = Position::query()->with('department')->get();
        $existingNumbers = Employee::query()->pluck('employee_number')->mapWithKeys(fn ($number) => [Str::lower($number) => true]);
        $seenEmployees = $seenEmails = $seenBioIds = [];
        $rows = [];
        $dataRows = array_filter($sheetRows, fn (array $values, int $row) => $row > 1 && array_filter($values, fn ($value) => trim((string) $value) !== ''), ARRAY_FILTER_USE_BOTH);
        if (count($dataRows) > 5000) {
            throw ValidationException::withMessages(['file' => 'Import files are limited to 5,000 employee rows.']);
        }

        foreach ($dataRows as $excelRow => $values) {
            $data = array_fill_keys(array_unique(array_merge(self::FIELDS, ['email'], $customKeys)), null);
            foreach ($mappedHeaders as $index => $field) {
                $value = trim((string) ($values[$index] ?? ''));
                $data[$field] = $value === '' ? null : $value;
            }
            $data['employee_number'] = $data['employee_number'] === null ? null : trim((string) $data['employee_number']);
            $data['email'] = $data['email'] === null ? null : Str::lower($data['email']);
            $errors = [];
            $numberKey = Str::lower($data['employee_number'] ?? '');
            $alreadyExists = $existingNumbers->has($numberKey);
            foreach ($requiredKeys as $field) {
                if ($data[$field] === null || trim((string) $data[$field]) === '') {
                    $errors[] = str_replace('_', ' ', ucfirst($field)).' is required.';
                }
            }
            if ($data['employee_number'] && isset($seenEmployees[$numberKey])) {
                $errors[] = 'Duplicate employee number in this workbook.';
            }
            if ($data['employee_number']) {
                $seenEmployees[$numberKey] = true;
            }

            $department = $data['department'] ? $departments->first(fn (Department $candidate) => strcasecmp($candidate->name, $data['department']) === 0 || strcasecmp($candidate->code, $data['department']) === 0) : null;
            $positionCandidates = $data['position'] ? $positions->filter(fn (Position $candidate) => strcasecmp($candidate->title, $data['position']) === 0 || strcasecmp($candidate->code, $data['position']) === 0) : collect();
            if ($department) {
                $positionCandidates = $positionCandidates->where('department_id', $department->id);
            }
            $position = $positionCandidates->count() === 1 ? $positionCandidates->first() : null;
            if ($data['department'] && ! $department) {
                $errors[] = 'Department must match an existing department name or code.';
            }
            if ($data['position'] && ! $position) {
                $errors[] = $positionCandidates->count() > 1 ? 'Position is ambiguous; enter its Department too.' : 'Position must match an existing position title or code.';
            }

            if ($alreadyExists) {
                $errors = ['Employee Number already exists.'];
            }
            if (! $alreadyExists && $data['email']) {
                if (isset($seenEmails[$data['email']])) {
                    $errors[] = 'Duplicate email address in this workbook.';
                }
                $seenEmails[$data['email']] = true;
            }
            if ($data['date_hired'] !== null && ctype_digit($data['date_hired']) && (int) $data['date_hired'] <= 2958465) {
                $data['date_hired'] = Carbon::create(1899, 12, 30)->addDays((int) $data['date_hired'])->toDateString();
            }
            if ($data['biometric_id'] !== null && ctype_digit($data['biometric_id'])) {
                $data['biometric_id'] = ltrim($data['biometric_id'], '0') ?: '0';
                if (! $alreadyExists && isset($seenBioIds[$data['biometric_id']])) {
                    $errors[] = 'Duplicate Bio ID in this workbook.';
                }
                if (! $alreadyExists) {
                    $seenBioIds[$data['biometric_id']] = true;
                }
            }
            $validator = Validator::make($data, [
                'employee_number' => ['required', 'string', 'max:255'],
                'first_name' => ['required', 'string', 'max:255'], 'middle_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'], 'suffix' => ['nullable', 'string', 'max:255'],
                'employment_status' => ['required', 'string', 'max:255', Rule::in($this->template->statuses())],
                'department' => ['required', 'string', 'max:255'], 'position' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
                'gender' => ['nullable', 'string', 'max:255'],
                'date_hired' => ['nullable', 'date_format:Y-m-d'],
                'biometric_id' => ['nullable', 'integer', 'between:1,4294967295', Rule::unique('employees', 'biometric_id'), Rule::unique('attendance_biometric_links', 'biometric_id')],
            ]);
            if ($validator->fails()) {
                $errors = array_merge($errors, $validator->errors()->all());
            }

            $data['department_id'] = $department?->id;
            $data['position_id'] = $position?->id;
            $rows[] = [
                'row' => $excelRow, 'employee_number' => $data['employee_number'] ?? '—',
                'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                'department' => $department->name ?? '—', 'position' => $position->title ?? '—',
                'status' => $data['employment_status'] ?? '—',
                'profile_action' => $alreadyExists ? 'Already exists' : 'Create profile',
                'account_action' => $alreadyExists ? 'Skip' : 'Create account',
                'username' => $data['employee_number'],
                'valid' => ! $errors, 'errors' => array_values(array_unique($errors)), 'data' => $data,
            ];
        }

        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'No employees were found below the row 1 headers.']);
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{rejected: list<array{row: int, errors: list<string>}>, credentials: list<ActivationSlip>}
     */
    public function commit(array $rows, User $actor, int $expiryDays): array
    {
        $credentials = $rejected = [];

        foreach ($rows as $row) {
            if ($row['valid']) {
                $data = $row['data'];
                $attributes = array_intersect_key($data, array_flip(self::FIELDS));
                unset($attributes['department'], $attributes['position']);
                $attributes['department_id'] = $data['department_id'];
                $attributes['position_id'] = $data['position_id'];
                $custom = array_filter($data, fn ($value, $key) => str_starts_with($key, 'custom_') && $value !== null, ARRAY_FILTER_USE_BOTH);
                if ($custom !== []) {
                    $attributes['custom_fields'] = $custom;
                }

                try {
                    $credentials[] = $this->accounts->create($attributes, $data['email'], $actor, $expiryDays);

                    continue;
                } catch (ValidationException $exception) {
                    $row['errors'] = collect($exception->errors())->flatten()->all();
                } catch (UniqueConstraintViolationException) {
                    $row['errors'] = ['An employee number, email, or Bio ID already exists.'];
                }
            }

            $rejected[] = ['row' => $row['row'], 'errors' => $row['errors']];
        }

        $created = count($credentials);
        AuditLog::query()->create([
            'user_id' => $actor->id, 'action' => 'employee.spreadsheet_imported', 'model_type' => Employee::class,
            'metadata' => json_encode(['created_accounts' => $created, 'rejected_rows' => count($rejected)], JSON_THROW_ON_ERROR),
        ]);

        return ['rejected' => $rejected, 'credentials' => $credentials];
    }

    /** @return array<int, array<int, string>> */
    private function sheetRows(string $path, string $sheetName): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'The Excel workbook could not be opened.']);
        }
        $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'), SimpleXMLElement::class, LIBXML_NONET);
        $rels = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'), SimpleXMLElement::class, LIBXML_NONET);
        if (! $workbook || ! $rels) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'This file is not a readable .xlsx workbook. Download the Employee import template and use its EMPLOYEES sheet.']);
        }
        $sheetId = null;
        foreach ($workbook->xpath('//*[local-name()="sheet"]') ?: [] as $sheet) {
            if ((string) $sheet['name'] === $sheetName) {
                $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $sheetId = (string) $attributes['id'];
                break;
            }
        }
        $target = null;
        foreach ($rels->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
            if ((string) $relationship['Id'] === $sheetId) {
                $target = 'xl/'.ltrim((string) $relationship['Target'], '/');
                break;
            }
        }
        if (! $target) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'This workbook is missing its EMPLOYEES sheet. Download the Employee import template to get the correct sheet.']);
        }
        $shared = [];
        $sharedStat = $zip->statName('xl/sharedStrings.xml');
        if ($sharedStat && $sharedStat['size'] > 20 * 1024 * 1024) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'The workbook shared text is too large to import.']);
        }
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sharedReader = XMLReader::XML($sharedXml, null, LIBXML_NONET | LIBXML_COMPACT);
            if (! $sharedReader instanceof XMLReader) {
                throw ValidationException::withMessages(['file' => 'The workbook shared text could not be read.']);
            }
            while ($sharedReader->read()) {
                if ($sharedReader->nodeType === XMLReader::ELEMENT && $sharedReader->localName === 'si') {
                    $node = simplexml_load_string($sharedReader->readOuterXml(), SimpleXMLElement::class, LIBXML_NONET);
                    if ($node === false) {
                        throw ValidationException::withMessages(['file' => 'The workbook shared text could not be read.']);
                    }
                    $shared[] = implode('', array_map('strval', (array) $node->xpath('.//*[local-name()="t"]')));
                }
            }
            $sharedReader->close();
        }
        $sheetStat = $zip->statName($target);
        if (! $sheetStat || $sheetStat['size'] > 50 * 1024 * 1024) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'The EMPLOYEES worksheet is missing or too large to import.']);
        }
        $sheetXml = $zip->getFromName($target);
        $zip->close();
        if ($sheetXml === false) {
            throw ValidationException::withMessages(['file' => 'The workbook must contain an EMPLOYEES worksheet.']);
        }
        $reader = XMLReader::XML($sheetXml, null, LIBXML_NONET | LIBXML_COMPACT);
        if (! $reader instanceof XMLReader) {
            throw ValidationException::withMessages(['file' => 'The EMPLOYEES worksheet could not be read.']);
        }
        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }
            $rowNode = simplexml_load_string($reader->readOuterXml(), SimpleXMLElement::class, LIBXML_NONET);
            if ($rowNode === false) {
                throw ValidationException::withMessages(['file' => 'The EMPLOYEES worksheet could not be read.']);
            }
            $rowNumber = (int) $rowNode['r'];
            $cells = [];
            foreach ($rowNode->c as $cell) {
                preg_match('/^([A-Z]+)/', (string) $cell['r'], $match);
                $column = 0;
                foreach (str_split($match[1] ?? 'A') as $letter) {
                    $column = $column * 26 + ord($letter) - 64;
                }
                $column--;
                $type = (string) $cell['t'];
                $value = (string) ($cell->v ?? '');
                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = implode('', array_map('strval', (array) $cell->xpath('.//*[local-name()="t"]')));
                }
                $cells[$column] = $value;
            }
            if ($cells) {
                ksort($cells);
                $rows[$rowNumber] = array_replace(array_fill(0, max(array_keys($cells)) + 1, ''), $cells);
            }
        }
        $reader->close();

        return $rows;
    }
}
