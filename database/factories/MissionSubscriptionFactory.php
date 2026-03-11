<?php

namespace Database\Factories;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionSubscription>
 */
class MissionSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'member_id' => Member::factory(),
            'status' => $this->faker->randomElement(PRFMissionSubscriptionStatus::getElements()),
            'mission_role' => $this->faker->randomElement(PRFMissionRole::getElements()),
            'invited_to_group' => $this->faker->boolean(),
            'invited_to_group_at' => $this->faker->optional()->dateTime(),
            'notes' => null,
        ];
    }
}
