<?php

namespace App\Policies;

use App\Models\CohortMission;
use App\Models\User;

class CohortMissionPolicy
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
        return $user->can(CohortMission::permission('viewAny'));
    }

    public function view(User $user, CohortMission $cohortMission): bool
    {
        return $user->can(CohortMission::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(CohortMission::permission('create'));
    }

    public function update(User $user, CohortMission $cohortMission): bool
    {
        return $user->can(CohortMission::permission('edit'));
    }

    public function delete(User $user, CohortMission $cohortMission): bool
    {
        return $user->can(CohortMission::permission('delete'));
    }

    public function restore(User $user, CohortMission $cohortMission): bool
    {
        return $user->can(CohortMission::permission('restore'));
    }

    public function forceDelete(User $user, CohortMission $cohortMission): bool
    {
        return $user->can(CohortMission::permission('forceDelete'));
    }
}
