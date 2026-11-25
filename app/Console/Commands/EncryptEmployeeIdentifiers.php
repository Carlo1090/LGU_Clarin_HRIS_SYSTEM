<?php

namespace App\Console\Commands;

use App\Casts\EmployeeIdentifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EncryptEmployeeIdentifiers extends Command
{
    protected $signature = 'employees:encrypt-identifiers
        {--apply : Write changes. Omit for a dry run}
        {--reverse : Restore encrypted identifiers to their original values}
        {--backup-verified : Confirm that a backup was restored and verified}';

    protected $description = 'Dry-run, encrypt, or restore employee TIN and GSIS identifiers using the application key';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $reverse = (bool) $this->option('reverse');
        if ($apply && (! $this->option('backup-verified')
            || Schema::getColumnType('employees', 'tin') !== 'text'
            || Schema::getColumnType('employees', 'gsis_bp_number') !== 'text')) {
            $this->error('Apply requires the widened columns and a verified backup.');

            return self::FAILURE;
        }

        $rows = $affected = $changes = 0;
        try {
            DB::table('employees')->select('id', 'tin', 'gsis_bp_number')->chunkById(200, function ($employees) use ($apply, $reverse, &$rows, &$affected, &$changes): void {
                foreach ($employees as $employee) {
                    [$changed, $count] = $apply
                        ? DB::transaction(fn () => $this->convert(DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first(), $reverse, true), attempts: 3)
                        : $this->convert($employee, $reverse, false);
                    $rows++;
                    $affected += (int) $changed;
                    $changes += $count;
                }
            });
        } catch (\Throwable) {
            $this->error('Stopped after scanning '.$rows.' employee rows. Check the schema and application key before resuming.');

            return self::FAILURE;
        }

        $this->info(($apply ? 'Applied' : 'Dry run').": {$rows} employee rows scanned; {$affected} affected; {$changes} identifier values.");

        return self::SUCCESS;
    }

    /** @return array{bool, int} */
    private function convert(?object $employee, bool $reverse, bool $apply): array
    {
        if ($employee === null) {
            return [false, 0];
        }

        $updates = [];
        foreach (['tin', 'gsis_bp_number'] as $field) {
            $value = $employee->{$field};
            if ($value === null) {
                continue;
            }
            if (str_starts_with($value, EmployeeIdentifier::PREFIX)) {
                $plaintext = EmployeeIdentifier::decrypt($value);
                if ($reverse) {
                    $updates[$field] = $plaintext;
                }
            } elseif (! $reverse) {
                $updates[$field] = EmployeeIdentifier::encrypt($value);
            }
        }
        if ($apply && $updates !== []) {
            DB::table('employees')->where('id', $employee->id)->update($updates);
        }

        return [$updates !== [], count($updates)];
    }
}
