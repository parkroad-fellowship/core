<?php

namespace App\Policies;

use App\Models\Speaker;

class SpeakerPolicy extends BasePolicy
{
    protected string $modelClass = Speaker::class;
}
