<?php

namespace App\Policies;

use App\Models\Membership;

class MembershipPolicy extends BasePolicy
{
    protected string $modelClass = Membership::class;
}
