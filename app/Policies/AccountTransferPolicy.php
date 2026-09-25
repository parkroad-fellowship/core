<?php

namespace App\Policies;

use App\Models\AccountTransfer;

class AccountTransferPolicy extends BasePolicy
{
    protected string $modelClass = AccountTransfer::class;
}
