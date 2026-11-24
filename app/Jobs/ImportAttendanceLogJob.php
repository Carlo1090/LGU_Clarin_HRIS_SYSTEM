<?php

namespace App\Jobs;

use App\Attendance\Services\AttendanceRecomputer;
use App\Models\AttendanceImport;
use App\Models\User;
use App\Services\AttendanceLogParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportAttendanceLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;

    public function __construct(
        public int $batchId,
        public int $actorId,
        public string $storedPath,
        public string $originalName,
    ) {}

    public function handle(AttendanceLogParser $parser, AttendanceRecomputer $recomputer): void
    {
        $batch = AttendanceImport::query()->findOrFail($this->batchId);
        $actor = User::query()->findOrFail($this->actorId);
        $absolutePath = Storage::disk('local')->path($this->storedPath);

        try {
            $file = new UploadedFile(
                $absolutePath,
                $this->originalName,
                null,
                null,
                true,
            );
            DB::transaction(function () use ($batch, $file, $actor, $parser, $recomputer): void {
                $rows = $parser->importIntoBatch($batch, $file, $actor);
                $summary = $recomputer->recomputeImport($rows);
                $batch->update(['recompute_summary' => [
                    'employees_affected' => count($summary['employee_ids']),
                    'unlinked_machine_ids' => $summary['unlinked_machine_ids'],
                ]]);
            });
        } catch (Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
            ]);
            throw $exception;
        }

        Storage::disk('local')->delete($this->storedPath);
    }

    public function failed(Throwable $exception): void
    {
        AttendanceImport::query()->whereKey($this->batchId)->update([
            'status' => 'failed',
            'error_message' => Str::limit($exception->getMessage(), 1000, ''),
        ]);
        Storage::disk('local')->delete($this->storedPath);
    }
}
