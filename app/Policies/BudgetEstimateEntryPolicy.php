<?php

namespace App\Policies;

use App\Models\BudgetEstimateEntry;

class BudgetEstimateEntryPolicy extends BasePolicy
{
    protected string $modelClass = BudgetEstimateEntry::class;
}
