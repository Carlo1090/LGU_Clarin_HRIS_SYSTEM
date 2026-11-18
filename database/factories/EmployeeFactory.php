<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'employee_number' => fake()->unique()->numerify('EMP-#####'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'suffix' => fake()->randomElement([null, 'Jr.', 'Sr.', 'III']),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'employment_status' => 'active',
            'date_hired' => fake()->dateTimeBetween('-15 years', '-1 year')->format('Y-m-d'),
            'date_regularized' => fake()->boolean(70) ? fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d') : null,
            'photo_path' => null,
        ];
    }
}
