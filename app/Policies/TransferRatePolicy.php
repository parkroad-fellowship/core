<?php

namespace App\Policies;

use App\Models\TransferRate;

class TransferRatePolicy extends BasePolicy
{
    protected string $modelClass = TransferRate::class;
}
