<?php

namespace App\Policies;

use App\Models\BudgetEstimate;

class BudgetEstimatePolicy extends BasePolicy
{
    protected string $modelClass = BudgetEstimate::class;
}
