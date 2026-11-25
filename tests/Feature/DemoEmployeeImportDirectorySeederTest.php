<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
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

        $this->assertDatabaseCount('departments', 15);
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Existing Department']);
        $this->assertDatabaseCount('positions', 1);
        $this->assertDatabaseHas('positions', [
            'department_id' => $department->id,
            'code' => 'DEMO-IMPORT-CLERK',
            'title' => 'Demo Import Clerk',
        ]);
    }

    public function test_all_official_departments_and_demo_position_are_created_when_directory_is_empty(): void
    {
        $seeder = new DemoEmployeeImportDirectorySeeder;

        $seeder->run();
        $seeder->run();

        $department = Department::query()->where('code', 'MAYOR')->firstOrFail();

        $this->assertDatabaseCount('departments', 14);
        foreach (Department::PLANTILLA_OFFICES as $code => $name) {
            $this->assertDatabaseHas('departments', ['code' => $code, 'name' => $name]);
        }
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create();
        $this->withoutVite()->actingAs($hr)->get(route('hris.resources.index', ['resource' => 'employees']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('fields', function ($fields): bool {
                $options = collect($fields)->firstWhere('name', 'department_id')['options'];
                $labels = collect($options)->pluck('label')->sort()->values()->all();
                $expected = collect(Department::PLANTILLA_OFFICES)->sort()->values()->all();

                return $labels === $expected;
            }));
        $this->assertDatabaseCount('positions', 1);
        $this->assertDatabaseHas('positions', [
            'department_id' => $department->id,
            'code' => 'DEMO-IMPORT-CLERK',
            'title' => 'Demo Import Clerk',
        ]);
    }
}
