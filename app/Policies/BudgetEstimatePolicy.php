<?php

namespace App\Policies;

use App\Models\BudgetEstimate;
use App\Models\User;

class BudgetEstimatePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(BudgetEstimate::permission('viewAny'));
    }

    public function view(User $user, BudgetEstimate $budgetEstimate): bool
    {
        return $user->can(BudgetEstimate::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(BudgetEstimate::permission('create'));
    }

    public function update(User $user, BudgetEstimate $budgetEstimate): bool
    {
        return $user->can(BudgetEstimate::permission('edit'));
    }

    public function delete(User $user, BudgetEstimate $budgetEstimate): bool
    {
        return $user->can(BudgetEstimate::permission('delete'));
    }

    public function restore(User $user, BudgetEstimate $budgetEstimate): bool
    {
        return $user->can(BudgetEstimate::permission('restore'));
    }

    public function forceDelete(User $user, BudgetEstimate $budgetEstimate): bool
    {
        return $user->can(BudgetEstimate::permission('forceDelete'));
    }
}
