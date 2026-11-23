<?php

namespace Tests\Feature;

use App\Models\Department;
use Database\Seeders\DemoEmployeeImportDirectorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoEmployeeImportDirectorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_position_is_added_to_an_existing_department_idempotently(): void
    {
        $department = Department::query()->create([
            'code' => 'EXISTING-DEPT',
            'name' => 'Existing Department',
        ]);
        $seeder = new DemoEmployeeImportDirectorySeeder;

        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('departments', 1);
        $this->assertDatabaseCount('positions', 1);
        $this->assertDatabaseHas('positions', [
            'department_id' => $department->id,
            'code' => 'DEMO-IMPORT-CLERK',
            'title' => 'Demo Import Clerk',
        ]);
    }
}
