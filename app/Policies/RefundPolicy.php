<?php

namespace App\Policies;

use App\Models\Refund;

class RefundPolicy extends BasePolicy
{
    protected string $modelClass = Refund::class;
}
