<?php

namespace App\Policies;

use App\Models\PRFEvent;

class EventPolicy extends BasePolicy
{
    protected string $modelClass = PRFEvent::class;
}
