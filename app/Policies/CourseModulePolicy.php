<?php

namespace App\Policies;

use App\Models\CourseModule;

class CourseModulePolicy extends BasePolicy
{
    protected string $modelClass = CourseModule::class;
}
