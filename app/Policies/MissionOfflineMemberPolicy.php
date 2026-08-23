<?php

namespace App\Policies;

use App\Models\MissionOfflineMember;

class MissionOfflineMemberPolicy extends BasePolicy
{
    protected string $modelClass = MissionOfflineMember::class;
}
