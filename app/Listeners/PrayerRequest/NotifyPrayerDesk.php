<?php

namespace App\Listeners\PrayerRequest;

use App\Events\PrayerRequest\PrayerRequestCreated;
use App\Jobs\PrayerRequest\NotifyPrayerDeskJob;

class NotifyPrayerDesk
{
    public function handle(PrayerRequestCreated $event): void
    {
        NotifyPrayerDeskJob::dispatch($event->prayerRequest);
    }
}
