<?php

namespace Database\Factories;

use App\Enums\PRFPledgeFrequency;
use App\Enums\PRFPledgeStatus;
use App\Models\Member;
use App\Models\Pledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pledge>
 */
class PledgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::query()->inRandomOrder()->first()?->getKey(),
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'frequency' => $this->faker->randomElement(PRFPledgeFrequency::getElements()),
            'start_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(PRFPledgeStatus::getElements()),
        ];
    }
}
