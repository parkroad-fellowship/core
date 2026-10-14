<?php

namespace Database\Factories;

use App\Enums\PRFMorphType;
use App\Enums\PRFTransactionType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\TransferRate;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitCost = $this->faker->numberBetween(200, 500);
        $quantity = $this->faker->numberBetween(1, 10);
        $lineTotal = $unitCost * $quantity;
        $chargeType = PRFTransactionType::MPESA_DEFAULT;

        return [
            'expense_category_id' => $this->existingOrNew(ExpenseCategory::class),
            'member_id' => $this->existingOrNew(Member::class),
            'charge_type' => $chargeType->value,
            'expenseable_type' => PRFMorphType::MISSION_EXPENSE->value,
            'unit_cost' => $unitCost,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'charge' => TransferRate::query()
                ->where([
                    'transaction_type' => $chargeType->value,
                    ['min_amount', '<=', $lineTotal],
                    ['max_amount', '>=', $lineTotal],
                ])
                ->first()
                ->charge,
            'confirmation_message' => $this->faker->sentence,
            'narration' => $this->faker->sentence,
        ];
    }
}
