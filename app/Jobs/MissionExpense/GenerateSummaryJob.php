<?php

namespace App\Jobs\MissionExpense;

use App\Enums\PRFMpesaTransactionType;
use App\Models\Expense;
use App\Models\MissionExpense;
use App\Models\MpesaRate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Class GenerateSummaryJob.
 * 
 * @package App\Jobs\MissionExpense
 * 
 * This job is responsible for generating a summary of the expenses for a mission expenditure 
 * and is meant to be triggered after a 
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
        Log::error('Mission Expense: ', [$missionExpense]);

        // Update the balance when line items are changed
        $allExpenses = $missionExpense->expenses;

        $totals = $allExpenses->sum('line_total');
        $allCharges = $allExpenses->sum('charge');
        $amountSpent = $totals + $allCharges;
        $amountToRefund = ($missionExpense->amount_received + $missionExpense->token_amount) - ($amountSpent +  $missionExpense->amount_refunded);

        $missionExpense->balance = $missionExpense->amount_received - ($amountSpent +  $missionExpense->amount_refunded);

        // Refund Charge
        if ($amountToRefund > 0) {
            $refundCharge = MpesaRate::query()
                ->where([
                    'transaction_type' => PRFMpesaTransactionType::DEFAULT->value,
                    ['min_amount', '<=', $amountToRefund],
                    ['max_amount', '>=', $amountToRefund],
                ])
                ->first()
                ->charge;
            $missionExpense->amount_to_refund = $amountToRefund - $refundCharge;
            $missionExpense->refund_charge = $refundCharge;
        } else {
            $missionExpense->is_refunded = true;
        }

        // Save all the changes
        $missionExpense->save();
    }
}
