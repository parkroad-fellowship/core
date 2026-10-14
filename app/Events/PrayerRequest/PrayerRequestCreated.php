<?php

namespace App\Events\PrayerRequest;

use App\Models\PrayerRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A prayer request was submitted.
 */
class PrayerRequestCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PrayerRequest $prayerRequest,
    ) {}
}
