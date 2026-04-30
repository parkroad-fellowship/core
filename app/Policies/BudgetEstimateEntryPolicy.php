<?php

namespace App\Policies;

use App\Models\BudgetEstimateEntry;
use App\Models\User;

class BudgetEstimateEntryPolicy
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
        return $user->can(BudgetEstimateEntry::permission('viewAny'));
    }

    public function view(User $user, BudgetEstimateEntry $budgetEstimateEntry): bool
    {
        return $user->can(BudgetEstimateEntry::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(BudgetEstimateEntry::permission('create'));
    }

    public function update(User $user, BudgetEstimateEntry $budgetEstimateEntry): bool
    {
        return $user->can(BudgetEstimateEntry::permission('edit'));
    }

    public function delete(User $user, BudgetEstimateEntry $budgetEstimateEntry): bool
    {
        return $user->can(BudgetEstimateEntry::permission('delete'));
    }

    public function restore(User $user, BudgetEstimateEntry $budgetEstimateEntry): bool
    {
        return $user->can(BudgetEstimateEntry::permission('restore'));
    }

    public function forceDelete(User $user, BudgetEstimateEntry $budgetEstimateEntry): bool
    {
        return $user->can(BudgetEstimateEntry::permission('forceDelete'));
    }
}
