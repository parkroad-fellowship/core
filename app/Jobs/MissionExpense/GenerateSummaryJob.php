<?php

namespace App\Jobs\MissionExpense;

use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\MissionExpense;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class GenerateSummaryJob.
 */
class GenerateSummaryJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MissionExpense $missionExpense,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $missionExpense = $this->missionExpense;

        // Update the balance when line items are changed
        $allExpenses = $missionExpense->expenses;

        $totals = $allExpenses->sum('line_total');
        $allCharges = $allExpenses->sum('charge');
        $amountSpent = $totals + $allCharges;
        $amountToRefund = ($missionExpense->amount_received + $missionExpense->token_amount) - ($amountSpent + $missionExpense->amount_refunded);

        $missionExpense->balance = $missionExpense->amount_received - ($amountSpent + $missionExpense->amount_refunded);
        $missionExpense->amount_spent = $amountSpent;

        // Refund Charge
        if ($amountToRefund > 0) {
            $refundCharge = Utils::getCharge(
                chargeType: PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
                amount: $amountToRefund,
            );
            $missionExpense->amount_to_refund = $amountToRefund - $refundCharge;
            $missionExpense->refund_charge = $refundCharge;
        } else {
            $missionExpense->is_refunded = true;
        }

        // Save all the changes
        $missionExpense->save();
    }
}
