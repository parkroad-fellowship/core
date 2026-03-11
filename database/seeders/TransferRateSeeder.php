<?php

namespace Database\Seeders;

use App\Enums\PRFTransactionType;
use App\Models\TransferRate;
use Illuminate\Database\Seeder;

class TransferRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $charges = [
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 1,
                'max_amount' => 49,
                'charge' => 0,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 50,
                'max_amount' => 100,
                'charge' => 0,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 101,
                'max_amount' => 500,
                'charge' => 7,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 501,
                'max_amount' => 1_000,
                'charge' => 13,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 1_001,
                'max_amount' => 1_500,
                'charge' => 23,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 1_501,
                'max_amount' => 2_500,
                'charge' => 33,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 2_501,
                'max_amount' => 3_500,
                'charge' => 53,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 3_501,
                'max_amount' => 5_000,
                'charge' => 57,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 5_001,
                'max_amount' => 7_500,
                'charge' => 78,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 7_501,
                'max_amount' => 10_000,
                'charge' => 90,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 10_001,
                'max_amount' => 15_000,
                'charge' => 100,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 15_001,
                'max_amount' => 20_000,
                'charge' => 105,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 20_001,
                'max_amount' => 35_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 35_001,
                'max_amount' => 50_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_DEFAULT->value,
                'min_amount' => 50_001,
                'max_amount' => 350_000,
                'charge' => 108,
            ],

            // Other Registered User
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 1,
                'max_amount' => 49,
                'charge' => 0,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 50,
                'max_amount' => 100,
                'charge' => 0,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 101,
                'max_amount' => 500,
                'charge' => 7,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 501,
                'max_amount' => 1_000,
                'charge' => 13,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 1_001,
                'max_amount' => 1_500,
                'charge' => 23,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 1_501,
                'max_amount' => 2_500,
                'charge' => 33,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 2_501,
                'max_amount' => 3_500,
                'charge' => 53,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 3_501,
                'max_amount' => 5_000,
                'charge' => 57,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 5_001,
                'max_amount' => 7_500,
                'charge' => 78,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 7_501,
                'max_amount' => 10_000,
                'charge' => 90,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 10_001,
                'max_amount' => 15_000,
                'charge' => 100,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 15_001,
                'max_amount' => 20_000,
                'charge' => 105,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 20_001,
                'max_amount' => 35_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 35_001,
                'max_amount' => 50_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_OTHER_REGISTERED_USER->value,
                'min_amount' => 50_001,
                'max_amount' => 350_000,
                'charge' => 108,
            ],

            // Agent Withdrawal
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 50,
                'max_amount' => 100,
                'charge' => 11,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 101,
                'max_amount' => 500,
                'charge' => 29,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 501,
                'max_amount' => 1_000,
                'charge' => 29,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 1_001,
                'max_amount' => 1_500,
                'charge' => 29,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 1_501,
                'max_amount' => 2_500,
                'charge' => 29,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 2_501,
                'max_amount' => 3_500,
                'charge' => 52,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 3_501,
                'max_amount' => 5_000,
                'charge' => 69,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 5_001,
                'max_amount' => 7_500,
                'charge' => 87,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 7_501,
                'max_amount' => 10_000,
                'charge' => 115,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 10_001,
                'max_amount' => 15_000,
                'charge' => 167,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 15_001,
                'max_amount' => 20_000,
                'charge' => 185,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 20_001,
                'max_amount' => 35_000,
                'charge' => 197,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 35_001,
                'max_amount' => 50_000,
                'charge' => 278,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_AGENT_WITHDRAWAL->value,
                'min_amount' => 50_001,
                'max_amount' => 350_000,
                'charge' => 309,
            ],

            // ATM Withdrawal
            [
                'transaction_type' => PRFTransactionType::MPESA_ATM_WITHDRAWAL->value,
                'min_amount' => 200,
                'max_amount' => 2_500,
                'charge' => 35,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_ATM_WITHDRAWAL->value,
                'min_amount' => 2_501,
                'max_amount' => 5_000,
                'charge' => 69,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_ATM_WITHDRAWAL->value,
                'min_amount' => 5_001,
                'max_amount' => 10_000,
                'charge' => 115,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_ATM_WITHDRAWAL->value,
                'min_amount' => 10_001,
                'max_amount' => 35_000,
                'charge' => 203,
            ],

            // PRF Paybill
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 1,
                'max_amount' => 49,
                'charge' => 0,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 50,
                'max_amount' => 100,
                'charge' => 0,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 101,
                'max_amount' => 500,
                'charge' => 5,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 501,
                'max_amount' => 1_000,
                'charge' => 10,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 1_001,
                'max_amount' => 1_500,
                'charge' => 15,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 1_501,
                'max_amount' => 2_500,
                'charge' => 20,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 2_501,
                'max_amount' => 3_500,
                'charge' => 25,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 3_501,
                'max_amount' => 5_000,
                'charge' => 34,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 5_001,
                'max_amount' => 7_500,
                'charge' => 42,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 7_501,
                'max_amount' => 10_000,
                'charge' => 48,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 10_001,
                'max_amount' => 15_000,
                'charge' => 57,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 15_001,
                'max_amount' => 20_000,
                'charge' => 62,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 20_001,
                'max_amount' => 25_000,
                'charge' => 67,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 25_001,
                'max_amount' => 30_000,
                'charge' => 72,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 30_001,
                'max_amount' => 35_000,
                'charge' => 83,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 35_001,
                'max_amount' => 40_000,
                'charge' => 99,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 40_001,
                'max_amount' => 45_000,
                'charge' => 103,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 40_001,
                'max_amount' => 45_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 45_001,
                'max_amount' => 50_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 50_001,
                'max_amount' => 70_000,
                'charge' => 108,
            ],
            [
                'transaction_type' => PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
                'min_amount' => 70_001,
                'max_amount' => 250_000,
                'charge' => 108,
            ],
        ];

        foreach ($charges as $charge) {
            TransferRate::updateOrCreate(
                [
                    'transaction_type' => $charge['transaction_type'],
                    'min_amount' => $charge['min_amount'],
                    'max_amount' => $charge['max_amount'],
                ],
                $charge
            );
        }
    }
}
