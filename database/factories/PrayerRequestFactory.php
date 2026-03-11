<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\PrayerRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrayerRequest>
 */
class PrayerRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'title' => fake()->word(),
            'description' => fake()->paragraph(),
        ];
    }
}
