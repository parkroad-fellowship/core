<?php

namespace App\Policies;

use App\Models\GroupMember;

class GroupMemberPolicy extends BasePolicy
{
    protected string $modelClass = GroupMember::class;
}
