<?php

namespace App\Policies;

use App\Models\CohortLetter;

class CohortLetterPolicy extends BasePolicy
{
    protected string $modelClass = CohortLetter::class;
}
