<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class PlantillaDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Department::PLANTILLA_OFFICES as $code => $name) {
            Department::firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
