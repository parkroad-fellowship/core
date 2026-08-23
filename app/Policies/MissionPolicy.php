<?php

namespace App\Policies;

use App\Models\Mission;

class MissionPolicy extends BasePolicy
{
    protected string $modelClass = Mission::class;
}
