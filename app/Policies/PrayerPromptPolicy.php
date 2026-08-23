<?php

namespace App\Policies;

use App\Models\PrayerPrompt;

class PrayerPromptPolicy extends BasePolicy
{
    protected string $modelClass = PrayerPrompt::class;
}
