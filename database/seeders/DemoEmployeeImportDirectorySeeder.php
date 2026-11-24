<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoEmployeeImportDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::query()->where('code', 'MAYOR')->first()
            ?? Department::query()->orderBy('id')->first();

        if ($department === null) {
            $department = Department::firstOrCreate(
                ['code' => 'MAYOR'],
                ['name' => Department::PLANTILLA_OFFICES['MAYOR']],
            );
        }

        DB::transaction(function () use ($department): void {
            Position::firstOrCreate(
                ['code' => 'DEMO-IMPORT-CLERK'],
                [
                    'department_id' => $department->id,
                    'title' => 'Demo Import Clerk',
                    'job_grade' => 'SG-5',
                    'is_active' => true,
                ],
            );
        });

        $this->command?->info("Ensured Demo Import Clerk under {$department->name} ({$department->code}).");
    }
}
