<?php

namespace App\Policies;

use App\Models\Pledge;

class PledgePolicy extends BasePolicy
{
    protected string $modelClass = Pledge::class;
}
