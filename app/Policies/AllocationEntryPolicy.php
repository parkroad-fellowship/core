<?php

namespace App\Policies;

use App\Models\AllocationEntry;

class AllocationEntryPolicy extends BasePolicy
{
    protected string $modelClass = AllocationEntry::class;
}
