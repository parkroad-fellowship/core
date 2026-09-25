<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\PrayerPrompt;
use App\Models\PrayerResponse;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrayerResponse>
 */
class PrayerResponseFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prayer_prompt_id' => $this->existingOrNew(PrayerPrompt::class),
            'member_id' => $this->existingOrNew(Member::class),
        ];
    }
}
