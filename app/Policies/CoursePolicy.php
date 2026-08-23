<?php

namespace App\Policies;

use App\Models\Course;

class CoursePolicy extends BasePolicy
{
    protected string $modelClass = Course::class;
}
