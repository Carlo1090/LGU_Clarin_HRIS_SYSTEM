<?php

namespace App\Services;

use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceLogParser
{
    public function __construct(
        private BiometricLinkVerifier $verifier,
        private AttendancePunchDeriver $deriver,
    ) {}

    /**
     * @return array{
     *     total_rows: int,
     *     date_range: array{from: string|null, to: string|null},
     *     distinct_machine_ids: int,
     *     machine_ids: list<int>,
     *     unlinked_ids: list<int>,
     *     would_skip: int,
     *     invalid_count: int,
     *     errors: list<array{line: int, reason: string}>
     * }
     */
    public function preview(UploadedFile $file): array
    {
        $parsed = $this->parseFile($file);
        $rows = $parsed['rows'];
        $ids = array_values(array_unique(array_column($rows, 'machine_user_id')));
        sort($ids);

        $linkedIds = Employee::query()->whereIn('biometric_id', $ids)->pluck('biometric_id')
            ->map(static fn ($id): int => (int) $id)->all();
        $unlinkedIds = array_values(array_diff($ids, $linkedIds));

        return [
            'total_rows' => $parsed['total_rows'],
            'date_range' => [
                'from' => $rows === [] ? null : min(array_column($rows, 'punched_at')),
                'to' => $rows === [] ? null : max(array_column($rows, 'punched_at')),
            ],
            'distinct_machine_ids' => count($ids),
            'machine_ids' => $ids,
            'unlinked_ids' => $unlinkedIds,
            'would_skip' => $this->countExistingOrRepeatedRows($rows),
            'invalid_count' => count($parsed['errors']),
            'errors' => $parsed['errors'],
        ];
    }

    public function import(UploadedFile $file, User $actor): AttendanceImport
    {
        $path = $file->getPathname();
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw ValidationException::withMessages(['file' => 'The log file could not be read.']);
        }

        $parsed = $this->parseFile($file);

        return DB::transaction(function () use ($file, $actor, $hash, $parsed): AttendanceImport {
            $stamp = now(config('attendance.timezone', 'Asia/Manila'));
            $batch = AttendanceImport::query()->create([
                'file_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                'file_sha256' => $hash,
                'uploaded_by' => $actor->id,
                'imported_at' => $stamp,
                'status' => 'completed',
            ]);

            $inserted = $duplicate = 0;
            foreach (array_chunk($parsed['rows'], 500) as $chunk) {
                $rows = array_map(fn (array $row): array => $this->storageRow($row, $batch->id, $stamp), $chunk);
                $written = AttendancePunch::query()->insertOrIgnore($rows);
                $inserted += $written;
                $duplicate += count($rows) - $written;
                $this->deriver->refreshRows($chunk);
            }

            $counts = [
                'total_lines' => $parsed['total_rows'],
                'inserted_count' => $inserted,
                'duplicate_count' => $duplicate,
                'invalid_count' => count($parsed['errors']),
            ];
            $batch->update($counts + ['invalid_lines' => array_slice($parsed['errors'], 0, 1000)]);
            $this->verifier->verifyPending($actor);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'attendance.logs_imported',
                'model_type' => AttendanceImport::class,
                'model_id' => $batch->id,
                'metadata' => json_encode($counts, JSON_THROW_ON_ERROR),
            ]);

            return $batch;
        }, attempts: 3);
    }

    public function importIntoBatch(AttendanceImport $batch, UploadedFile $file, User $actor): void
    {
        $parsed = $this->parseFile($file);
        $stamp = now(config('attendance.timezone', 'Asia/Manila'));
        $inserted = $duplicate = 0;

        foreach (array_chunk($parsed['rows'], 500) as $chunk) {
            $rows = array_map(fn (array $row): array => $this->storageRow($row, $batch->id, $stamp), $chunk);
            $written = AttendancePunch::query()->insertOrIgnore($rows);
            $inserted += $written;
            $duplicate += count($rows) - $written;
        }
        $this->deriver->refreshRows($parsed['rows']);

        $counts = [
            'total_lines' => $parsed['total_rows'],
            'inserted_count' => $inserted,
            'duplicate_count' => $duplicate,
            'invalid_count' => count($parsed['errors']),
        ];
        $batch->update($counts + [
            'status' => 'completed',
            'imported_at' => $stamp,
            'invalid_lines' => array_slice($parsed['errors'], 0, 1000),
        ]);
        $this->verifier->verifyPending($actor);

        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'attendance.logs_imported',
            'model_type' => AttendanceImport::class,
            'model_id' => $batch->id,
            'metadata' => json_encode($counts, JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array{
     *     total_rows: int,
     *     rows: list<array{machine_user_id: int, punched_at: string, device_id: int, punch_state: int, verify_mode: int, work_code: int}>,
     *     errors: list<array{line: int, reason: string}>
     * }
     */
    private function parseFile(UploadedFile $file): array
    {
        $path = $file->getPathname();
        if (($stream = fopen($path, 'rb')) === false) {
            throw ValidationException::withMessages(['file' => 'The log file could not be read.']);
        }

        try {
            $lineNumber = $totalRows = 0;
            $rows = $errors = [];

            while (($line = fgets($stream)) !== false) {
                $lineNumber++;
                if ($lineNumber === 1 && str_starts_with($line, "\xEF\xBB\xBF")) {
                    $line = substr($line, 3);
                }
                if (trim($line, " \t\r\n") === '') {
                    continue;
                }

                $totalRows++;
                $parsed = $this->parseLine($line);
                if (is_string($parsed)) {
                    $errors[] = ['line' => $lineNumber, 'reason' => $parsed];

                    continue;
                }
                $rows[] = $parsed;
            }

            if (! feof($stream)) {
                throw ValidationException::withMessages(['file' => 'The log file could not be read completely.']);
            }
            if ($totalRows === 0) {
                throw ValidationException::withMessages(['file' => 'The log file contains no punch lines.']);
            }

            return ['total_rows' => $totalRows, 'rows' => $rows, 'errors' => $errors];
        } finally {
            fclose($stream);
        }
    }

    /** @return array{machine_user_id: int, punched_at: string, device_id: int, punch_state: int, verify_mode: int, work_code: int}|string */
    private function parseLine(string $line): array|string
    {
        $columns = explode("\t", rtrim($line, "\r\n"));
        if (count($columns) !== 6) {
            return 'Expected exactly six tab-separated columns.';
        }

        $machineId = trim($columns[0]);
        if (! preg_match('/^[0-9]+$/D', $machineId) || (float) $machineId > 4294967295) {
            return 'Machine user ID must be an unsigned integer.';
        }

        $punchedAt = $columns[1];
        if (! preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $punchedAt)) {
            return 'Use a timestamp in YYYY-MM-DD HH:MM:SS format.';
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $punchedAt);
        if (! $date || $date->format('Y-m-d H:i:s') !== $punchedAt) {
            return 'Use a valid timestamp in YYYY-MM-DD HH:MM:SS format.';
        }

        $values = [];
        foreach (array_slice($columns, 2) as $index => $column) {
            $value = trim($column);
            if (! preg_match('/^[+-]?[0-9]+$/D', $value)) {
                return 'Device, state, verification, and work-code columns must be integers.';
            }
            $number = (int) $value;
            if ($index === 0 && ($number < 0 || $number > 4294967295)) {
                return 'Device ID must be an unsigned integer.';
            }
            if ($index > 0 && ($number < -32768 || $number > 32767)) {
                return 'Punch state, verification mode, and work code must fit a small integer.';
            }
            $values[] = $number;
        }

        return [
            'machine_user_id' => (int) $machineId,
            'punched_at' => $punchedAt,
            'device_id' => $values[0],
            'punch_state' => $values[1],
            'verify_mode' => $values[2],
            'work_code' => $values[3],
        ];
    }

    /**
     * @param  array{machine_user_id: int, punched_at: string, device_id: int, punch_state: int, verify_mode: int, work_code: int}  $row
     * @return array<string, int|string|bool|null>
     */
    private function storageRow(array $row, int $batchId, \DateTimeInterface $createdAt): array
    {
        $flags = [];
        $slot = AttendancePunchDeriver::slotForState($row['punch_state']);
        $expected = $this->deriver->expectedSlot($row['punched_at']);
        if ($slot === null) {
            $flags = ['unknown_punch_state' => true];
        }
        if ($expected !== null && $slot !== $expected) {
            $flags['suspected_wrong_button'] = true;
            $flags['suggested_slot'] = $expected;
        }
        if ($row['verify_mode'] === 0) {
            $flags['pin_verify'] = true;
        }

        return $row + [
            'biometric_id' => $row['machine_user_id'],
            'attendance_import_id' => $batchId,
            'import_batch_id' => $batchId,
            'raw_3' => $row['device_id'],
            'raw_4' => $row['punch_state'],
            'raw_5' => $row['verify_mode'],
            'raw_6' => $row['work_code'],
            'derived_slot' => $slot,
            'is_duplicate' => false,
            'flags' => $this->encodeJson($flags),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  list<array{machine_user_id: int, punched_at: string, device_id: int, punch_state: int, verify_mode: int, work_code: int}>  $rows
     */
    private function countExistingOrRepeatedRows(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }
        $ids = array_values(array_unique(array_column($rows, 'machine_user_id')));
        $known = [];
        foreach (array_chunk($ids, 300) as $idChunk) {
            $matches = AttendancePunch::query()->whereIn('machine_user_id', $idChunk)
                ->whereBetween('punched_at', [
                    min(array_column($rows, 'punched_at')),
                    max(array_column($rows, 'punched_at')),
                ])->get(['machine_user_id', 'punched_at', 'device_id']);
            foreach ($matches as $match) {
                $known[$match->machine_user_id.'|'.$match->punched_at.'|'.$match->device_id] = true;
            }
        }

        $seen = [];
        $skipped = 0;
        foreach ($rows as $row) {
            $key = $this->uniqueKey($row);
            if (isset($known[$key]) || isset($seen[$key])) {
                $skipped++;
            }
            $seen[$key] = true;
        }

        return $skipped;
    }

    /** @param array{machine_user_id: int, punched_at: string, device_id: int} $row */
    private function uniqueKey(array $row): string
    {
        return $row['machine_user_id'].'|'.$row['punched_at'].'|'.$row['device_id'];
    }

    /** @param array<string, bool|string> $value */
    private function encodeJson(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
