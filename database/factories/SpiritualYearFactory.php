<?php

namespace Database\Factories;

use App\Models\SpiritualYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpiritualYear>
 */
class SpiritualYearFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Year ' . $this->faker->unique()->numberBetween(1, 99),
        ];
    }
}
