<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'title' => fake()->jobTitle(),
            'code' => fake()->unique()->bothify('POS-####'),
            'job_grade' => 'SG-'.fake()->numberBetween(1, 25),
            'is_active' => true,
        ];
    }
}
