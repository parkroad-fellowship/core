<?php

namespace App\Policies;

use App\Models\ConnectedAccount;

class ConnectedAccountPolicy extends BasePolicy
{
    protected string $modelClass = ConnectedAccount::class;
}
