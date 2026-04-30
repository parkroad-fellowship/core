<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
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
        return $user->can(Membership::permission('viewAny'));
    }

    public function view(User $user, Membership $membership): bool
    {
        return $user->can(Membership::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Membership::permission('create'));
    }

    public function update(User $user, Membership $membership): bool
    {
        return $user->can(Membership::permission('edit'));
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $user->can(Membership::permission('delete'));
    }

    public function restore(User $user, Membership $membership): bool
    {
        return $user->can(Membership::permission('restore'));
    }

    public function forceDelete(User $user, Membership $membership): bool
    {
        return $user->can(Membership::permission('forceDelete'));
    }
}
