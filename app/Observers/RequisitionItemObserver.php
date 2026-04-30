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
        $this->recalculateTotalAmount($requisitionItem->requisition_id);
    }

    /**
     * Handle the RequisitionItem "updated" event.
     */
    public function updated(RequisitionItem $requisitionItem): void
    {
        $this->recalculateTotalAmount($requisitionItem->requisition_id);
    }

    /**
     * Handle the RequisitionItem "deleted" event.
     */
    public function deleted(RequisitionItem $requisitionItem): void
    {
        $this->recalculateTotalAmount($requisitionItem->requisition_id);
    }

    /**
     * Handle the RequisitionItem "restored" event.
     */
    public function restored(RequisitionItem $requisitionItem): void
    {
        $this->recalculateTotalAmount($requisitionItem->requisition_id);
    }

    /**
     * Handle the RequisitionItem "force deleted" event.
     */
    public function forceDeleted(RequisitionItem $requisitionItem): void
    {
        $this->recalculateTotalAmount($requisitionItem->requisition_id);
    }

    private function recalculateTotalAmount(int $requisitionId): void
    {
        $totalAmount = RequisitionItem::query()
            ->where('requisition_id', $requisitionId)
            ->sum('total_price');

        Requisition::query()
            ->where('id', $requisitionId)
            ->update(['total_amount' => $totalAmount]);
    }
}
