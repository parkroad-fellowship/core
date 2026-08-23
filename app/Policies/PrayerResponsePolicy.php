<?php

namespace App\Policies;

use App\Models\PrayerResponse;

class PrayerResponsePolicy extends BasePolicy
{
    protected string $modelClass = PrayerResponse::class;
}
