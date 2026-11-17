<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
{
    protected $model = Applicant::class;

    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'application_date' => fake()->date(),
            'status' => 'applied',
        ];
    }
}
