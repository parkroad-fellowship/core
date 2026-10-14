<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionWhatsAppGroupLinked;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;

class InviteSubscribersToWhatsAppGroup
{
    public function handle(MissionWhatsAppGroupLinked $event): void
    {
        NotifyWhatsAppGroupJob::dispatch($event->mission);
    }
}
