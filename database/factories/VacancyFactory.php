<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'title' => fake()->jobTitle(),
            'open_positions' => fake()->numberBetween(1, 3),
            'closing_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'status' => 'open',
        ];
    }
}
