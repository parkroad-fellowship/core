<?php

namespace Database\Factories;

use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

class MissionOfflineMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Mission::query()->inRandomOrder()->first()?->getKey(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
