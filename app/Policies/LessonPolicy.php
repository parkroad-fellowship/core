<?php

namespace App\Policies;

use App\Models\Lesson;

class LessonPolicy extends BasePolicy
{
    protected string $modelClass = Lesson::class;
}
