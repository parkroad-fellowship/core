<?php

namespace App\Policies;

use App\Models\Group;

class GroupPolicy extends BasePolicy
{
    protected string $modelClass = Group::class;
}
