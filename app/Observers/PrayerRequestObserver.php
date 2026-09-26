<?php

namespace App\Observers;

use App\Events\PrayerRequest\PrayerRequestCreated;
use App\Models\PrayerRequest;

/**
 * Translates PrayerRequest lifecycle changes into domain events; side effects live in listeners.
 */
class PrayerRequestObserver
{
    public function created(PrayerRequest $prayerRequest): void
    {
        PrayerRequestCreated::dispatch($prayerRequest);
    }
}
