<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\User;

class MissionPolicy
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
        return $user->can(Mission::permission('viewAny'));
    }

    public function view(User $user, Mission $mission): bool
    {
        return $user->can(Mission::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Mission::permission('create'));
    }

    public function update(User $user, Mission $mission): bool
    {
        return $user->can(Mission::permission('edit'));
    }

    public function delete(User $user, Mission $mission): bool
    {
        return $user->can(Mission::permission('delete'));
    }
}
