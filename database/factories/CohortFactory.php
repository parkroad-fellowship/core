<?php

namespace Database\Factories;

use App\Enums\PRFActiveStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cohort>
 */
class CohortFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'start_date' => $this->faker->date(),
            'is_active' => $this->faker->randomElement(PRFActiveStatus::getElements()),
        ];
    }
}
