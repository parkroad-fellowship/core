<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MissionExpense>
 */
class MissionExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'amount_received' => $this->faker->numberBetween(200, 30_0000),
            'amount_spent' => 0,
            'token_amount' => 0,
            'amount_to_refund' => 0,
            'amount_refunded' => 0,
            'is_refunded' => false,
            'balance' => 0,
        ];
    }
}
