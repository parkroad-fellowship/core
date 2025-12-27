<?php

namespace App\Observers;

use App\Models\Requisition;
use App\Models\RequisitionItem;

class RequisitionItemObserver
{
    /**
     * Handle the RequisitionItem "created" event.
     */
    public function created(RequisitionItem $requisitionItem): void
    {
        Requisition::query()
            ->where('id', $requisitionItem->requisition_id)
            ->increment('total_amount', $requisitionItem->total_price);
    }

    /**
     * Handle the RequisitionItem "updated" event.
     */
    public function updated(RequisitionItem $requisitionItem): void
    {
        $original = $requisitionItem->getOriginal();
        $changed = $requisitionItem->getChanges();

        if (isset($changed['total_price'])) {
            Requisition::query()
                ->where('id', $requisitionItem->requisition_id)
                ->increment('total_amount', $changed['total_price'] - $original['total_price']);
        }
    }

    /**
     * Handle the RequisitionItem "deleted" event.
     */
    public function deleted(RequisitionItem $requisitionItem): void
    {
        Requisition::query()
            ->where('id', $requisitionItem->requisition_id)
            ->decrement('total_amount', $requisitionItem->total_price);
    }

    /**
     * Handle the RequisitionItem "restored" event.
     */
    public function restored(RequisitionItem $requisitionItem): void
    {
        Requisition::query()
            ->where('id', $requisitionItem->requisition_id)
            ->increment('total_amount', $requisitionItem->total_price);
    }

    /**
     * Handle the RequisitionItem "force deleted" event.
     */
    public function forceDeleted(RequisitionItem $requisitionItem): void
    {
        Requisition::query()
            ->where('id', $requisitionItem->requisition_id)
            ->decrement('total_amount', $requisitionItem->total_price);
    }
}
