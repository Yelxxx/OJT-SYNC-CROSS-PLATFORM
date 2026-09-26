<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => User::ROLE_STUDENT]),
            'student_no' => fake()->unique()->numerify('2024-####'),
            'program' => fake()->randomElement(['BSIT', 'BSCS', 'BSCpE']),
            'year_level' => fake()->randomElement(['3rd Year', '4th Year']),
            'required_hours' => 300,
            'status' => 'active',
        ];
    }
}
