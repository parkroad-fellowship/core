<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\PrayerRequest;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrayerRequest>
 */
class PrayerRequestFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => $this->existingOrNew(Member::class),
            'title' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
