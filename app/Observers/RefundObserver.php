<?php

namespace App\Observers;

use App\Models\LedgerEntry;
use App\Models\Refund;

class RefundObserver
{
    /**
     * A refund owns its cashbook lines, so the balances and the accountability sheet always agree.
     */
    public function deleted(Refund $refund): void
    {
        LedgerEntry::query()
            ->where('refund_id', $refund->id)
            ->get()
            ->each(fn(LedgerEntry $entry) => $entry->delete());
    }

    public function restored(Refund $refund): void
    {
        LedgerEntry::onlyTrashed()
            ->where('refund_id', $refund->id)
            ->get()
            ->each(fn(LedgerEntry $entry) => $entry->restore());
    }
}
