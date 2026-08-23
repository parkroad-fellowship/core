<?php

namespace App\Observers;

use App\Enums\PRFMorphType;
use App\Jobs\AccountingEvent\CreateDefaultRequisitionJob;
use App\Models\AccountingEvent;

class AccountingEventObserver
{
    /**
     * Handle the AccountingEvent "created" event.
     */
    public function created(AccountingEvent $accountingEvent): void
    {
        // If this is a mission, create a default requisition based on the
        // school's budget estimate for the mission type
        if ($accountingEvent->accounting_eventable_type === PRFMorphType::MISSION) {
            CreateDefaultRequisitionJob::dispatch($accountingEvent);
        }
    }

    /**
     * Handle the AccountingEvent "updated" event.
     */
    public function updated(AccountingEvent $accountingEvent): void
    {
        //
    }

    /**
     * Handle the AccountingEvent "deleted" event.
     */
    public function deleted(AccountingEvent $accountingEvent): void
    {
        //
    }

    /**
     * Handle the AccountingEvent "restored" event.
     */
    public function restored(AccountingEvent $accountingEvent): void
    {
        //
    }

    /**
     * Handle the AccountingEvent "force deleted" event.
     */
    public function forceDeleted(AccountingEvent $accountingEvent): void
    {
        //
    }
}
