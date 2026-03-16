<?php

namespace App\Policies;

use App\Models\GroupMember;
use App\Models\User;

class GroupMemberPolicy
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
        return $user->can(GroupMember::permission('viewAny'));
    }

    public function view(User $user, GroupMember $groupMember): bool
    {
        return $user->can(GroupMember::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(GroupMember::permission('create'));
    }

    public function update(User $user, GroupMember $groupMember): bool
    {
        return $user->can(GroupMember::permission('edit'));
    }

    public function delete(User $user, GroupMember $groupMember): bool
    {
        return $user->can(GroupMember::permission('delete'));
    }

    public function restore(User $user, GroupMember $groupMember): bool
    {
        return $user->can(GroupMember::permission('restore'));
    }

    public function forceDelete(User $user, GroupMember $groupMember): bool
    {
        return $user->can(GroupMember::permission('forceDelete'));
    }
}
