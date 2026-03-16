<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
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
        return $user->can(Member::permission('viewAny'));
    }

    public function view(User $user, Member $member): bool
    {
        return $user->can(Member::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Member::permission('create'));
    }

    public function update(User $user, Member $member): bool
    {
        return $user->can(Member::permission('edit'));
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->can(Member::permission('delete'));
    }
}
