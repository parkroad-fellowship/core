<?php

namespace Database\Factories;

use App\Models\SchoolTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolTerm>
 */
class SchoolTermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'year' => $this->faker->year,
        ];
    }
}
