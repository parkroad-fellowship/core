<?php

namespace App\Listeners\PRFEvent;

use App\Enums\PRFEventType;
use App\Events\PRFEvent\PRFEventCreated;
use App\Events\PRFEvent\PRFEventOpenedToMembers;
use App\Jobs\PRFEvent\NotifyMembersJob;

class AnnounceMemberEvent
{
    public function handle(PRFEventCreated|PRFEventOpenedToMembers $event): void
    {
        if ($event->prfEvent->event_type === PRFEventType::MEMBER) {
            NotifyMembersJob::dispatch($event->prfEvent);
        }
    }
}
