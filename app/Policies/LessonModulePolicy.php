<?php

namespace App\Policies;

use App\Models\LessonModule;

class LessonModulePolicy extends BasePolicy
{
    protected string $modelClass = LessonModule::class;
}
