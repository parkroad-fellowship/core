<?php

namespace App\Policies;

use App\Models\MissionSession;

class MissionSessionPolicy extends BasePolicy
{
    protected string $modelClass = MissionSession::class;
}
