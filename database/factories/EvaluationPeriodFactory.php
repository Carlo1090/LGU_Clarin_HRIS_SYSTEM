<?php

namespace Database\Factories;

use App\Models\EvaluationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationPeriod>
 */
class EvaluationPeriodFactory extends Factory
{
    protected $model = EvaluationPeriod::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2020, 2035);

        return [
            'name' => 'Performance Review '.$year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'status' => 'draft',
        ];
    }
}
