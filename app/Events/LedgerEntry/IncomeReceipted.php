<?php

namespace App\Events\LedgerEntry;

use App\Models\LedgerEntry;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Income was recorded and given a receipt number. Listeners send the receipt when asked to.
 */
class IncomeReceipted implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LedgerEntry $ledgerEntry,
        public bool $sendReceipt = true,
    ) {}
}
