<?php

namespace App\Policies;

use App\Models\Church;

class ChurchPolicy extends BasePolicy
{
    protected string $modelClass = Church::class;
}
