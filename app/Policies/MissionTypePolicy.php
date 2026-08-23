<?php

namespace App\Policies;

use App\Models\MissionType;

class MissionTypePolicy extends BasePolicy
{
    protected string $modelClass = MissionType::class;
}
