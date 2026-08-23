<?php

namespace App\Policies;

use App\Models\LessonMember;

class LessonMemberPolicy extends BasePolicy
{
    protected string $modelClass = LessonMember::class;
}
