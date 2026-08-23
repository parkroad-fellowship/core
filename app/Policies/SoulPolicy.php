<?php

namespace App\Policies;

use App\Models\Soul;

class SoulPolicy extends BasePolicy
{
    protected string $modelClass = Soul::class;
}
