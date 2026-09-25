<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSession;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionSession>
 */
class MissionSessionFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->addWeeks(2);

        return [
            'mission_id' => $this->existingOrNew(Mission::class),
            'facilitator_id' => $this->existingOrNew(Member::class),
            'speaker_id' => optional(Member::query()->inRandomOrder()->first())->getKey(),
            'class_group_id' => optional(ClassGroup::query()->inRandomOrder()->first())->getKey(),
            'starts_at' => $startDate,
            'ends_at' => $startDate->copy()->addHours(2),
            'notes' => $this->faker->text(),
            'order' => 0,
        ];
    }
}
