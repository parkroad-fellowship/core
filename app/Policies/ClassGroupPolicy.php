<?php

namespace App\Policies;

use App\Models\ClassGroup;

class ClassGroupPolicy extends BasePolicy
{
    protected string $modelClass = ClassGroup::class;
}
