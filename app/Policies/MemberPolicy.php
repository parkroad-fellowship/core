<?php

namespace App\Policies;

use App\Models\Member;

class MemberPolicy extends BasePolicy
{
    protected string $modelClass = Member::class;
}
