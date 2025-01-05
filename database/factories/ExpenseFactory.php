<?php

namespace Database\Factories;

use App\Enums\PRFMorphType;
use App\Enums\PRFMpesaTransactionType;
use App\Models\Member;
use App\Models\Mission;
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
        $amount = $this->faker->numberBetween(200, 30_0000);

        return [
            'ulid' => $this->faker->unique()->ulid,
            'member_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'expensable_id' => Mission::query()->inRandomOrder()->first()->getKey(),
            'expensable_type' => PRFMorphType::MISSION->value,
            'amount' => $amount,
            'charge' => MpesaRate::query()
                ->where([
                    'transaction_type' => PRFMpesaTransactionType::DEFAULT->value,
                    ['min_amount', '<=', $amount],
                    ['max_amount', '>=', $amount],
                ])
                ->first()
                ->charge,
            'confirmation_message' => $this->faker->sentence,
        ];
    }
}
