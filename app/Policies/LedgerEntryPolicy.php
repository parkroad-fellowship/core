<?php

namespace App\Policies;

use App\Models\LedgerEntry;

class LedgerEntryPolicy extends BasePolicy
{
    protected string $modelClass = LedgerEntry::class;
}
