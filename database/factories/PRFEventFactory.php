<?php

namespace Database\Factories;

use App\Enums\PRFActiveStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PRFEvent>
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
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'venue' => $this->faker->address(),
            'capacity' => $this->faker->randomNumber(2),
            'status' => $this->faker->randomElement(PRFActiveStatus::getElements()),
        ];
    }
}
