<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '+1 month');

        return [
            'code' => fake()->unique()->bothify('PAY-####-##'),
            'start_date' => $startDate->format('Y-m-01'),
            'end_date' => $startDate->format('Y-m-t'),
            'status' => 'draft',
            'released_at' => null,
        ];
    }
}
