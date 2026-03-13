<?php

namespace App\Policies;

use App\Models\CohortLetter;
use App\Models\User;

class CohortLetterPolicy
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
        return $user->can(CohortLetter::permission('viewAny'));
    }

    public function view(User $user, CohortLetter $cohortLetter): bool
    {
        return $user->can(CohortLetter::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(CohortLetter::permission('create'));
    }

    public function update(User $user, CohortLetter $cohortLetter): bool
    {
        return $user->can(CohortLetter::permission('edit'));
    }

    public function delete(User $user, CohortLetter $cohortLetter): bool
    {
        return $user->can(CohortLetter::permission('delete'));
    }

    public function restore(User $user, CohortLetter $cohortLetter): bool
    {
        return $user->can(CohortLetter::permission('restore'));
    }

    public function forceDelete(User $user, CohortLetter $cohortLetter): bool
    {
        return $user->can(CohortLetter::permission('forceDelete'));
    }
}
