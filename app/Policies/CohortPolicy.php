<?php

namespace App\Policies;

use App\Models\Cohort;

class CohortPolicy extends BasePolicy
{
    protected string $modelClass = Cohort::class;
}
