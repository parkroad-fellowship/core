<?php

namespace App\Policies;

use App\Models\PRFEventParticipant;

class PRFEventParticipantPolicy extends BasePolicy
{
    protected string $modelClass = PRFEventParticipant::class;
}
