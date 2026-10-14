<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionExpense;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionExpense>
 */
class MissionExpenseFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => $this->existingOrNew(Mission::class),
            'amount_received' => $this->faker->numberBetween(5_000, 50_000),
            'amount_spent' => $this->faker->numberBetween(1_000, 5_000),
            'token_amount' => 0,
            'amount_to_refund' => 0,
            'amount_refunded' => 0,
            'is_refunded' => false,
            'balance' => 0,
            'refund_charge' => 0,
        ];
    }
}
