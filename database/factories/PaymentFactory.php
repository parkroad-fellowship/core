<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_type_id' => PaymentType::query()->inRandomOrder()->first()->getKey(),
            'member_id' => Member::query()->inRandomOrder()->first()->getKey(),
            'amount' => $this->faker->randomDigit(),
        ];
    }
}
