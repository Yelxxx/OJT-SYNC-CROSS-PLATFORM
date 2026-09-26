<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle().' Intern',
            'description' => fake()->paragraph(),
            'requirements' => fake()->sentence(),
            'slots' => 2,
            'applications_count' => 0,
            'status' => 'open',
        ];
    }
}
