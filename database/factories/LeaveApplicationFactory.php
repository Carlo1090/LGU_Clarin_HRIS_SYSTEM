<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveApplication>
 */
class LeaveApplicationFactory extends Factory
{
    protected $model = LeaveApplication::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => (clone $startDate)->modify('+1 day')->format('Y-m-d'),
            'days_requested' => 2,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'approved_by' => null,
        ];
    }
}
