<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_type_id' => $this->existingOrNew(PaymentType::class),
            'member_id' => $this->existingOrNew(Member::class),
            'amount' => $this->faker->randomDigit(),
        ];
    }
}
