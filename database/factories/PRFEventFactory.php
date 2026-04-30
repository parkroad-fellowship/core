<?php

namespace Database\Factories;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFResponsibleDesk;
use App\Models\PRFEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PRFEvent>
 */
class PRFEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(4),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'venue' => $this->faker->address(),
            'capacity' => $this->faker->randomNumber(2),
            'status' => $this->faker->randomElement(PRFActiveStatus::getElements()),
            'responsible_desk' => $this->faker->randomElement(PRFResponsibleDesk::getElements()),
        ];
    }
}
