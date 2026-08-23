<?php

namespace App\Policies;

use App\Models\MaritalStatus;

class MaritalStatusPolicy extends BasePolicy
{
    protected string $modelClass = MaritalStatus::class;
}
