<?php

namespace App\Listeners\PRFEvent;

use App\Events\PRFEvent\PRFEventCreated;
use App\Jobs\PRFEvent\CreateAccountingEventJob;

class CreatePRFEventAccountingEvent
{
    public function handle(PRFEventCreated $event): void
    {
        CreateAccountingEventJob::dispatchSync($event->prfEvent->id);
    }
}
