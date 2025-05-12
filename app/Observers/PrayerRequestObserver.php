<?php

namespace App\Observers;

use App\Jobs\PrayerRequest\NotifyPrayerDeskJob;
use App\Models\PrayerRequest;

class PrayerRequestObserver
{
    /**
     * Handle the PrayerRequest "created" event.
     */
    public function created(PrayerRequest $prayerRequest): void
    {
        NotifyPrayerDeskJob::dispatch($prayerRequest);
    }

    /**
     * Handle the PrayerRequest "updated" event.
     */
    public function updated(PrayerRequest $prayerRequest): void
    {
        //
    }

    /**
     * Handle the PrayerRequest "deleted" event.
     */
    public function deleted(PrayerRequest $prayerRequest): void
    {
        //
    }

    /**
     * Handle the PrayerRequest "restored" event.
     */
    public function restored(PrayerRequest $prayerRequest): void
    {
        //
    }

    /**
     * Handle the PrayerRequest "force deleted" event.
     */
    public function forceDeleted(PrayerRequest $prayerRequest): void
    {
        //
    }
}
