<?php

namespace Database\Factories;

use App\Models\DebriefNote;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebriefNote>
 */
class DebriefNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::query()->inRandomOrder()->first()->getKey(),
            'note' => $this->faker->text(),
        ];
    }
}
