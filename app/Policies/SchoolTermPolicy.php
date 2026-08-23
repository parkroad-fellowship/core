<?php

namespace App\Policies;

use App\Models\SchoolTerm;

class SchoolTermPolicy extends BasePolicy
{
    protected string $modelClass = SchoolTerm::class;
}
