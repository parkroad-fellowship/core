<?php

namespace App\Observers;

use App\Enums\PRFMpesaTransactionType;
use App\Models\Expense;
use App\Models\MissionExpense;
use App\Models\MpesaRate;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        $missionExpense = MissionExpense::query()
            ->where('id', $expense->expenseable_id)
            ->firstOrFail();

        $expensePlusCharge = $expense->line_total + $expense->charge;

        // Deduct the total amount spent minus the charge for that expense
        $amountSpent = $missionExpense->amount_spent + $expensePlusCharge;

        // Deduct the expense from the balance
        $newBalance = $missionExpense->balance - $expensePlusCharge;

        // Refund amount
        $amountToRefund = $newBalance + $missionExpense->token_amount;

        $refundCharge =  MpesaRate::where([
            'transaction_type' => PRFMpesaTransactionType::DEFAULT->value,
            ['min_amount', '<=', $expense->line_total],
            ['max_amount', '>=', $expense->line_total],
        ])->first()->charge;

        $missionExpense->update([
            'amount_spent' => $amountSpent,
            'balance' => $newBalance,
            'amount_to_refund' => $amountToRefund - $refundCharge,
            'refund_charge' => $refundCharge,
        ]);
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        //
    }
}
