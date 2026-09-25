<?php

namespace App\Observers;

use App\Models\AccountTransfer;
use App\Models\LedgerEntry;

class AccountTransferObserver
{
    /**
     * A transfer owns its cashbook lines.
     */
    public function deleted(AccountTransfer $accountTransfer): void
    {
        $accountTransfer
            ->ledgerEntries()
            ->get()
            ->each(fn(LedgerEntry $entry) => $entry->delete());
    }

    public function restored(AccountTransfer $accountTransfer): void
    {
        $accountTransfer
            ->ledgerEntries()
            ->onlyTrashed()
            ->get()
            ->each(fn(LedgerEntry $entry) => $entry->restore());
    }
}
