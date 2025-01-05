<?php

namespace Database\Factories;

use App\Enums\PRFChannelType;
use App\Enums\PRFMorphType;
use App\Enums\PRFMpesaTransactionType;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\MpesaRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitCost = $this->faker->numberBetween(200, 30_0000);
        $quantity = $this->faker->numberBetween(1, 10);

        return [
            'expense_category_id' => ExpenseCategory::query()->inRandomOrder()->first()->getKey(),
            'member_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'channel_type' => PRFChannelType::M_PESA->value,
            'charge_type' => PRFMpesaTransactionType::DEFAULT->value,
            'expenseable_type' => PRFMorphType::MISSION_EXPENSE->value,
            'unit_cost' => $unitCost,
            'charge' => MpesaRate::query()
                ->where([
                    'transaction_type' => PRFMpesaTransactionType::DEFAULT->value,
                    ['min_amount', '<=', $unitCost],
                    ['max_amount', '>=', $unitCost],
                ])
                ->first()
                ->charge,
            'confirmation_message' => $this->faker->sentence,
            'quantity' => $quantity,
            'line_total' => $unitCost * $quantity,
        ];
    }
}
