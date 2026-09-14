<?php

namespace Database\Factories;

use App\Enums\PRFPledgeInstallmentMethod;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PledgeInstallment>
 */
class PledgeInstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pledge_id' => Pledge::query()->inRandomOrder()->first()?->getKey(),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'fulfilled_on' => $this->faker->date(),
            'method' => $this->faker->randomElement(PRFPledgeInstallmentMethod::getElements()),
            'notes' => $this->faker->sentence(),
        ];
    }
}
