<?php

namespace App\Services;

use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\AuditLog;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceLogParser
{
    public function __construct(private BiometricLinkVerifier $verifier) {}

    public function import(UploadedFile $file, User $actor, int $offset): AttendanceImport
    {
        $path = $file->getPathname();
        $hash = hash_file('sha256', $path);
        if ($hash === false || ($stream = fopen($path, 'rb')) === false) {
            throw ValidationException::withMessages(['file' => 'The log file could not be read.']);
        }

        try {
            return DB::transaction(function () use ($file, $actor, $offset, $hash, $stream): AttendanceImport {
                // Retry must begin at the first line if the database detects a deadlock.
                rewind($stream);
                $stamp = now();
                $batch = AttendanceImport::query()->create([
                    'file_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                    'file_sha256' => $hash,
                    'uploaded_by' => $actor->id,
                    'imported_at' => $stamp,
                    'status' => 'completed',
                    'clock_offset_seconds' => $offset,
                ]);

                $lineNumber = $total = $inserted = $duplicate = $invalid = 0;
                $rows = $errors = [];
                while (($line = fgets($stream)) !== false) {
                    $lineNumber++;
                    if ($lineNumber === 1 && str_starts_with($line, "\xEF\xBB\xBF")) {
                        $line = substr($line, 3);
                    }
                    if (trim($line, " \t\r\n") === '') {
                        continue;
                    }
                    $total++;
                    $parsed = $this->parseLine($line, $offset);
                    if (is_string($parsed)) {
                        $invalid++;
                        // shortcut: show the first 200 errors in the report, persist per-line errors separately when full error exports are needed.
                        if (count($errors) < 200) {
                            $errors[] = ['line' => $lineNumber, 'reason' => $parsed];
                        }

                        continue;
                    }
                    $rows[] = $parsed + ['attendance_import_id' => $batch->id, 'created_at' => $stamp];
                    if (count($rows) === 500) {
                        $this->flush($rows, $inserted, $duplicate);
                    }
                }
                if (! feof($stream)) {
                    throw ValidationException::withMessages(['file' => 'The log file could not be read completely.']);
                }
                if ($total === 0) {
                    throw ValidationException::withMessages(['file' => 'The log file contains no punch lines.']);
                }
                $this->flush($rows, $inserted, $duplicate);
                $counts = ['total_lines' => $total, 'inserted_count' => $inserted, 'duplicate_count' => $duplicate, 'invalid_count' => $invalid];
                $batch->update($counts + ['invalid_lines' => $errors]);
                $this->verifier->verifyPending($actor);
                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'action' => 'attendance.logs_imported',
                    'model_type' => AttendanceImport::class,
                    'model_id' => $batch->id,
                    'metadata' => json_encode($counts + ['clock_offset_seconds' => $offset], JSON_THROW_ON_ERROR),
                ]);

                return $batch;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (AttendanceImport::query()->where('file_sha256', $hash)->exists()) {
                throw ValidationException::withMessages(['file' => 'This exact log file has already been imported.']);
            }

            throw $exception;
        } finally {
            fclose($stream);
        }
    }

    /** @param list<array<string, mixed>> $rows */
    private function flush(array &$rows, int &$inserted, int &$duplicate): void
    {
        if ($rows === []) {
            return;
        }
        $written = AttendancePunch::query()->insertOrIgnore($rows);
        $inserted += $written;
        $duplicate += count($rows) - $written;
        $rows = [];
    }

    /** @return array<string, int|string|null>|string */
    private function parseLine(string $line, int $offset): array|string
    {
        $columns = explode("\t", rtrim($line, "\r\n"), 7);
        if (count($columns) < 2 || count($columns) > 6) {
            return 'Expected two to six tab-separated columns.';
        }

        $id = ltrim(trim($columns[0]), '0');
        if (! ctype_digit($id) || strlen($id) > 10 || (int) $id > 4294967295) {
            return 'Biometric ID must be a positive whole number.';
        }

        $timestamp = trim($columns[1]);
        if (! preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $timestamp)) {
            return 'Use a valid timestamp in YYYY-MM-DD HH:MM:SS format.';
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $timestamp);
        if (! $date || $date->format('Y-m-d H:i:s') !== $timestamp) {
            return 'Use a valid timestamp in YYYY-MM-DD HH:MM:SS format.';
        }
        $corrected = $date->modify(sprintf('%+d seconds', $offset))->format('Y-m-d H:i:s');
        if (strlen($corrected) !== 19 || substr($corrected, 0, 4) < '1000') {
            return 'The clock adjustment puts the timestamp out of range.';
        }

        $row = ['biometric_id' => (int) $id, 'punched_at' => $corrected];
        for ($index = 2; $index < 6; $index++) {
            $value = trim($columns[$index] ?? '');
            if ($value !== '' && (! preg_match('/^[+-]?[0-9]+$/D', $value) || (int) $value < -32768 || (int) $value > 32767)) {
                return 'Machine columns must contain small integers.';
            }
            $row['raw_'.($index + 1)] = $value === '' ? null : (int) $value;
        }

        return $row;
    }
}
