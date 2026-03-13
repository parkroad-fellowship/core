<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
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
        return $user->can(Group::permission('viewAny'));
    }

    public function view(User $user, Group $group): bool
    {
        return $user->can(Group::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Group::permission('create'));
    }

    public function update(User $user, Group $group): bool
    {
        return $user->can(Group::permission('edit'));
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->can(Group::permission('delete'));
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->can(Group::permission('restore'));
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->can(Group::permission('forceDelete'));
    }
}
