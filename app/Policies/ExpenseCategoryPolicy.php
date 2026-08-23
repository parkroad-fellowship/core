<?php

namespace App\Policies;

use App\Models\ExpenseCategory;

class ExpenseCategoryPolicy extends BasePolicy
{
    protected string $modelClass = ExpenseCategory::class;
}
