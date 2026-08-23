<?php

namespace App\Policies;

use App\Models\Gift;

class GiftPolicy extends BasePolicy
{
    protected string $modelClass = Gift::class;
}
