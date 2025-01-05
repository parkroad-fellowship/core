<?php

namespace App\Observers;

use App\Models\MissionExpense;

class MissionExpenseObserver
{
    /**
     * Handle the MissionExpense "created" event.
     */
    public function created(MissionExpense $missionExpense): void
    {
        //
    }

    /**
     * Handle the MissionExpense "updated" event.
     */
    public function updated(MissionExpense $missionExpense): void
    {
        // Refund amount
        $amountToRefund = $missionExpense->amount_to_refund + (
            $missionExpense->balance
        );
    }

    /**
     * Handle the MissionExpense "deleted" event.
     */
    public function deleted(MissionExpense $missionExpense): void
    {
        //
    }

    /**
     * Handle the MissionExpense "restored" event.
     */
    public function restored(MissionExpense $missionExpense): void
    {
        //
    }

    /**
     * Handle the MissionExpense "force deleted" event.
     */
    public function forceDeleted(MissionExpense $missionExpense): void
    {
        //
    }
}
