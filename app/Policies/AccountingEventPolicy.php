<?php

namespace App\Policies;

use App\Models\AccountingEvent;

class AccountingEventPolicy extends BasePolicy
{
    protected string $modelClass = AccountingEvent::class;
}
