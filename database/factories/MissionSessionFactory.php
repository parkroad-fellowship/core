<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MissionSession>
 */
class MissionSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTime('+1 week');

        return [
            'mission_id' => Mission::query()->inRandomOrder()->first()->getKey(),
            'facilitator_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'speaker_id' => optional(Member::query()->inRandomOrder()->first())->getKey(),
            'class_group_id' => optional(ClassGroup::query()->inRandomOrder()->first())->getKey(),
            'starts_at' => $startDate,
            'ends_at' => $this->faker->dateTimeBetween($startDate, '+1 hour'),
            'notes' => $this->faker->text(),
            'order' => 0,
        ];
    }
}
