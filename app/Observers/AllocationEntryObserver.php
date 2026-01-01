<?php

namespace App\Observers;

use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\Refund;
use Illuminate\Support\Facades\Log;

class AllocationEntryObserver
{
    /**
     * Handle the AllocationEntry "created" event.
     */
    public function created(AllocationEntry $allocationEntry): void
    {
        $accountingEvent = AccountingEvent::find($allocationEntry->accounting_event_id);
        if (! $accountingEvent) {
            Log::warning("Accounting Event not found for Allocation Entry ID: {$allocationEntry->id}");

            return;
        }

        // Recalculate the deficit amount for the latest refund
        $latestRefund = $accountingEvent->latestRefund;

        if ($latestRefund) {
            // Sum all refunds that have been issued for this accounting event
            $totalRefunds = Refund::query()
                ->where('accounting_event_id', $accountingEvent->id)
                ->sum('amount');

            // deficit_amount = what's left to refund - what's already been refunded
            $latestRefund->deficit_amount = (int) $accountingEvent->amount_to_refund - (int) $totalRefunds;
            $latestRefund->save();
        }
    }

    /**
     * Handle the AllocationEntry "updated" event.
     */
    public function updated(AllocationEntry $allocationEntry): void
    {
        //
    }

    /**
     * Handle the AllocationEntry "deleted" event.
     */
    public function deleted(AllocationEntry $allocationEntry): void
    {
        //
    }

    /**
     * Handle the AllocationEntry "restored" event.
     */
    public function restored(AllocationEntry $allocationEntry): void
    {
        //
    }

    /**
     * Handle the AllocationEntry "force deleted" event.
     */
    public function forceDeleted(AllocationEntry $allocationEntry): void
    {
        //
    }
}
