<?php

namespace Database\Factories;

use App\Models\Pledge;
use App\Models\PledgeReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PledgeReminder>
 */
class PledgeReminderFactory extends Factory
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
            'due_on' => $this->faker->date(),
            'remind_on' => $this->faker->date(),
            'channel' => 'mail',
        ];
    }
}
