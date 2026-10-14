<?php

namespace App\Policies;

use App\Models\FinancialAccount;

class FinancialAccountPolicy extends BasePolicy
{
    protected string $modelClass = FinancialAccount::class;
}
