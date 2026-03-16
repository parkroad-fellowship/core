<?php

namespace App\Policies;

use App\Models\Cohort;
use App\Models\User;

class CohortPolicy
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
        return $user->can(Cohort::permission('viewAny'));
    }

    public function view(User $user, Cohort $cohort): bool
    {
        return $user->can(Cohort::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Cohort::permission('create'));
    }

    public function update(User $user, Cohort $cohort): bool
    {
        return $user->can(Cohort::permission('edit'));
    }

    public function delete(User $user, Cohort $cohort): bool
    {
        return $user->can(Cohort::permission('delete'));
    }

    public function restore(User $user, Cohort $cohort): bool
    {
        return $user->can(Cohort::permission('restore'));
    }

    public function forceDelete(User $user, Cohort $cohort): bool
    {
        return $user->can(Cohort::permission('forceDelete'));
    }
}
