<?php

namespace App\Policies;

use App\Models\School;

class SchoolPolicy extends BasePolicy
{
    protected string $modelClass = School::class;
}
