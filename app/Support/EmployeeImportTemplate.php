<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class EmployeeImportTemplate
{
    private const REQUIRED = ['employee_number', 'first_name', 'last_name', 'employment_status'];
    private const DEFAULT_COLUMNS = [
        ['key' => 'employee_number', 'label' => 'Employee Number'], ['key' => 'first_name', 'label' => 'First Name'],
        ['key' => 'middle_name', 'label' => 'Middle Name'], ['key' => 'last_name', 'label' => 'Last Name'],
        ['key' => 'suffix', 'label' => 'Suffix'], ['key' => 'gender', 'label' => 'Gender'],
        ['key' => 'birth_date', 'label' => 'Birth Date'], ['key' => 'department', 'label' => 'Department'],
        ['key' => 'position', 'label' => 'Position'], ['key' => 'employment_status', 'label' => 'Employment Status'],
        ['key' => 'date_hired', 'label' => 'Date Hired'], ['key' => 'date_regularized', 'label' => 'Date Regularized'],
        ['key' => 'age', 'label' => 'Age'], ['key' => 'tin', 'label' => 'TIN'],
        ['key' => 'date_of_original_appointment', 'label' => 'Date of Original Appointment'],
        ['key' => 'date_of_last_promotion_appointment', 'label' => 'Date of Last Promotion/Appointment'],
        ['key' => 'appointment_status', 'label' => 'Appointment Status'],
        ['key' => 'civil_service_eligibility', 'label' => 'Civil Service Eligibility'],
        ['key' => 'comment_annotation', 'label' => 'Comment/Annotation'], ['key' => 'pwd', 'label' => 'PWD'],
        ['key' => 'indigenous_people', 'label' => 'Indigenous People'],
        ['key' => 'solo_parent_id_number', 'label' => 'Solo Parent ID Number'],
        ['key' => 'gsis_bp_number', 'label' => 'GSIS BP Number'], ['key' => 'email', 'label' => 'Email Address'],
    ];

    private function path(): string
    {
        return storage_path('app/private/employee-import-columns.json');
    }

    public function columns(): array
    {
        $path = $this->path();
        if (! is_file($path)) return self::DEFAULT_COLUMNS;
        $saved = json_decode((string) file_get_contents($path), true);
        return is_array($saved) && isset($saved['columns']) ? $saved['columns'] : self::DEFAULT_COLUMNS;
    }

    public function statuses(): array
    {
        $saved = is_file($this->path()) ? json_decode((string) file_get_contents($this->path()), true) : null;
        return $saved['status_options'] ?? ['active', 'on leave', 'inactive', 'terminated'];
    }

    public function save(array $columns, array $statuses): void
    {
        $known = array_column(self::DEFAULT_COLUMNS, 'key');
        $oldCustom = array_filter(array_column($this->columns(), 'key'), fn ($key) => str_starts_with($key, 'custom_'));
        $normalized = [];
        foreach ($columns as $column) {
            $key = $column['key'] ?? '';
            if ($key === '') $key = 'custom_'.(Str::slug($column['label']) ?: 'field').'_'.Str::lower(Str::random(5));
            if (! in_array($key, $known, true) && ! in_array($key, $oldCustom, true)) {
                throw ValidationException::withMessages(['columns' => 'Column key is not supported.']);
            }
            $normalized[] = ['key' => $key, 'label' => trim($column['label'])];
        }
        $columns = $normalized;
        $keys = array_column($columns, 'key');
        $labels = array_map(fn ($label) => mb_strtolower(trim($label)), array_column($columns, 'label'));
        if (in_array('', $labels, true) || array_diff(self::REQUIRED, $keys) || count($keys) !== count(array_unique($keys)) || count($labels) !== count(array_unique($labels))) {
            throw ValidationException::withMessages(['columns' => 'Keep all required columns and use unique column names.']);
        }
        $removed = array_diff(array_column($this->columns(), 'key'), $keys);
        if (array_intersect(self::REQUIRED, $removed)) throw ValidationException::withMessages(['columns' => 'Required employee columns cannot be removed.']);
        $path = $this->path();
        if (! is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        file_put_contents($path, json_encode(['columns' => array_values($columns), 'status_options' => array_values($statuses)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
    }

    public function download(string $path, array $statuses): void
    {
        $columns = $this->columns();
        $eligibilities = json_decode(file_get_contents(resource_path('data/plantilla-lookups.json')), true, flags: JSON_THROW_ON_ERROR)['eligibilities'];
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Unable to create the employee workbook.');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><x:definedNames><x:definedName name="EligibilityOptions">\'Eligibilities\'!$A$1:$A$'.count($eligibilities).'</x:definedName></x:definedNames><x:sheets><x:sheet name="EMPLOYEES" sheetId="1" r:id="rId1"/><x:sheet name="Eligibilities" sheetId="2" r:id="rId2"/></x:sheets></x:workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');

        $widths = [20, 18, 18, 18, 14, 14, 16, 24, 22, 20, 16, 18, 10, 18, 22, 25, 20, 34, 30, 12, 20, 22, 20, 30];
        $cols = $header = '';
        foreach ($columns as $index => $column) {
            $number = $index + 1; $letter = $this->columnName($number);
            $cols .= '<x:col min="'.$number.'" max="'.$number.'" width="'.($widths[$index] ?? 22).'" customWidth="1"/>';
            $header .= '<x:c r="'.$letter.'1" s="1" t="inlineStr"><x:is><x:t>'.htmlspecialchars($column['label'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</x:t></x:is></x:c>';
        }
        $last = $this->columnName(count($columns));
        $validations = '';
        foreach ($columns as $index => $column) {
            $letter = $this->columnName($index + 1);
            if ($column['key'] === 'employment_status') {
                $validations .= '<x:dataValidation type="list" allowBlank="1" sqref="'.$letter.'2:'.$letter.'5001"><x:formula1>"'.htmlspecialchars(implode(',', $statuses), ENT_XML1 | ENT_QUOTES, 'UTF-8').'"</x:formula1></x:dataValidation>';
            }
            if ($column['key'] === 'civil_service_eligibility') {
                $validations .= '<x:dataValidation type="list" allowBlank="1" sqref="'.$letter.'2:'.$letter.'5001"><x:formula1>EligibilityOptions</x:formula1></x:dataValidation>';
            }
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:dimension ref="A1:'.$last.'5001"/><x:sheetViews><x:sheetView workbookViewId="0"><x:pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></x:sheetView></x:sheetViews><x:cols>'.$cols.'</x:cols><x:sheetData><x:row r="1" ht="56" customHeight="1">'.$header.'</x:row></x:sheetData><x:dataValidations count="'.substr_count($validations, '<x:dataValidation ').'">'.$validations.'</x:dataValidations></x:worksheet>');
        $lookup = '';
        foreach ($eligibilities as $index => $value) $lookup .= '<x:row r="'.($index + 1).'"><x:c r="A'.($index + 1).'" t="inlineStr"><x:is><x:t>'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</x:t></x:is></x:c></x:row>';
        $zip->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheetData>'.$lookup.'</x:sheetData></x:worksheet>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><x:styleSheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:fonts count="2"><x:font><x:sz val="11"/><x:name val="Calibri"/></x:font><x:font><x:b/><x:color rgb="FFFFFFFF"/><x:sz val="11"/><x:name val="Calibri"/></x:font></x:fonts><x:fills count="3"><x:fill><x:patternFill patternType="none"/></x:fill><x:fill><x:patternFill patternType="gray125"/></x:fill><x:fill><x:patternFill patternType="solid"><x:fgColor rgb="FF1F4E78"/><x:bgColor indexed="64"/></x:patternFill></x:fill></x:fills><x:borders count="1"><x:border><x:left/><x:right/><x:top/><x:bottom/><x:diagonal/></x:border></x:borders><x:cellStyleXfs count="1"><x:xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></x:cellStyleXfs><x:cellXfs count="2"><x:xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><x:xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></x:cellXfs></x:styleSheet>');
        $zip->close();
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) { $number--; $name = chr(65 + $number % 26).$name; $number = intdiv($number, 26); }
        return $name;
    }
}
