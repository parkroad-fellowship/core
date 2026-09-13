<?php

namespace App\Observers;

use App\Models\Pledge;
use App\Models\PledgeInstallment;

/**
 * Advances a pledge's due-date cadence whenever a follow-through installment
 * is recorded. This is the single source for due-date advancement so it works
 * for the Treasurer's Filament recording, the API job, and auto-reconciliation
 * alike.
 */
class PledgeInstallmentObserver
{
    public function created(PledgeInstallment $installment): void
    {
        $pledge = Pledge::query()->whereKey($installment->pledge_id)->first();

        if ($pledge) {
            $pledge->advanceDueDate($installment->fulfilled_on);
        }
    }
}
