<?php

namespace App\Policies;

use App\Models\CohortMission;

class CohortMissionPolicy extends BasePolicy
{
    protected string $modelClass = CohortMission::class;
}
