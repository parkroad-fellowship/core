<?php

namespace App\Policies;

use App\Models\MissionQuestion;

class MissionQuestionPolicy extends BasePolicy
{
    protected string $modelClass = MissionQuestion::class;
}
