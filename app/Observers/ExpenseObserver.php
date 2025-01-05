<?php

namespace App\Observers;

use App\Jobs\MissionExpense\GenerateSummaryJob;
use App\Models\Expense;
use App\Models\MissionExpense;

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

        GenerateSummaryJob::dispatch($missionExpense);
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
