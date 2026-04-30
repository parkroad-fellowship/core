<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentTypes = [
            [
                'name' => 'Missions',
                'description' => 'Giving towards missions',
            ],
            [
                'name' => 'Bible Study Fun Day',
                'description' => 'Giving towards Bible Study Fun Day',
            ],
            [
                'name' => 'Camp',
                'description' => 'Giving towards camp',
            ],
        ];

        foreach ($paymentTypes as $paymentType) {
            PaymentType::updateOrCreate([
                'name' => $paymentType['name'],
            ], [
                'name' => $paymentType['name'],
                'description' => $paymentType['description'],
            ]);
        }
    }
}
