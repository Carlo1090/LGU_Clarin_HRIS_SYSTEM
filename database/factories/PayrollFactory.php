<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $grossPay = fake()->randomFloat(2, 15000, 80000);

        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'gross_pay' => $grossPay,
            'net_pay' => $grossPay,
            'status' => 'draft',
        ];
    }
}
