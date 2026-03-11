<?php

namespace Database\Factories;

use App\Enums\PRFPromptFrequency;
use App\Enums\PRFPromptTime;
use App\Models\PrayerPrompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrayerPrompt>
 */
class PrayerPromptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => $this->faker->sentence,
            'frequency' => $this->faker->randomElement(PRFPromptFrequency::getElements()),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'time_of_day' => $this->faker->randomElement(PRFPromptTime::getElements()),
        ];
    }
}
