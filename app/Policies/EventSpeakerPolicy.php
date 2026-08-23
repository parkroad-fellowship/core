<?php

namespace App\Policies;

use App\Models\EventSpeaker;

class EventSpeakerPolicy extends BasePolicy
{
    protected string $modelClass = EventSpeaker::class;
}
