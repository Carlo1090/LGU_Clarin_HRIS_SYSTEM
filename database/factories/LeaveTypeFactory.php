<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Leave',
            'code' => fake()->unique()->bothify('LV-##'),
            'default_days' => fake()->numberBetween(5, 20),
            'is_paid' => true,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
