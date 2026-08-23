<?php

namespace App\Policies;

use App\Models\CourseMember;

class CourseMemberPolicy extends BasePolicy
{
    protected string $modelClass = CourseMember::class;
}
