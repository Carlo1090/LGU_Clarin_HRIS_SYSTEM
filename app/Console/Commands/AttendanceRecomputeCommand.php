<?php

namespace App\Console\Commands;

use App\Attendance\Services\AttendanceRecomputer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AttendanceRecomputeCommand extends Command
{
    protected $signature = 'attendance:recompute
        {--from= : Start work date (Y-m-d)}
        {--to= : End work date (Y-m-d)}
        {--employee= : Employee id}';

    protected $description = 'Recompute schedule-based attendance daily rows';

    public function handle(AttendanceRecomputer $recomputer): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $employee = $this->option('employee');

        $validator = Validator::make(
            compact('from', 'to', 'employee'),
            ['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d', 'employee' => 'nullable|integer|min:1'],
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if ($from !== null && $to !== null && $from > $to) {
            $this->error('The --from date must be before or equal to --to.');

            return self::FAILURE;
        }

        $summary = $recomputer->recompute(
            $employee !== null ? (int) $employee : null,
            is_string($from) ? $from : null,
            is_string($to) ? $to : null,
        );

        $this->info(sprintf(
            'Processed %d distinct work dates for %d selected employees.',
            count($summary['work_dates']),
            count($summary['employee_ids']),
        ));

        return self::SUCCESS;
    }
}
