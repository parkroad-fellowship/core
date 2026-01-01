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
            // Sum all refunds and charges
            $totalRefunds = Refund::query()
                ->where('accounting_event_id', $accountingEvent->id)
                ->sum('amount');

            $totalCharges = Refund::query()
                ->where('accounting_event_id', $accountingEvent->id)
                ->sum('charge');

            // Org accepts refund_charge, any charges beyond that are person's responsibility
            $extraCharges = max(0, (int) $totalCharges - (int) $accountingEvent->refund_charge);

            // deficit = what person still owes (including any extra charges from splits)
            $latestRefund->deficit_amount = (int) $accountingEvent->amount_to_refund - (int) $totalRefunds + $extraCharges;
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
