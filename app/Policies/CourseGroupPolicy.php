<?php

namespace App\Policies;

use App\Models\CourseGroup;

class CourseGroupPolicy extends BasePolicy
{
    protected string $modelClass = CourseGroup::class;
}
