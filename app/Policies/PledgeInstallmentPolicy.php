<?php

namespace App\Policies;

use App\Models\PledgeInstallment;

class PledgeInstallmentPolicy extends BasePolicy
{
    protected string $modelClass = PledgeInstallment::class;
}
