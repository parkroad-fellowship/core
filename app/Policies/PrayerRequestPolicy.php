<?php

namespace App\Policies;

use App\Models\PrayerRequest;

class PrayerRequestPolicy extends BasePolicy
{
    protected string $modelClass = PrayerRequest::class;
}
