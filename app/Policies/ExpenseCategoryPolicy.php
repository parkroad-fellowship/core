<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
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
        return $user->can(ExpenseCategory::permission('viewAny'));
    }

    public function view(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can(ExpenseCategory::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(ExpenseCategory::permission('create'));
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can(ExpenseCategory::permission('edit'));
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can(ExpenseCategory::permission('delete'));
    }
}
