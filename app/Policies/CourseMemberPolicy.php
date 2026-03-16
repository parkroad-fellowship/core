<?php

namespace App\Policies;

use App\Models\CourseMember;
use App\Models\User;

class CourseMemberPolicy
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
        return $user->can(CourseMember::permission('viewAny'));
    }

    public function view(User $user, CourseMember $courseMember): bool
    {
        return $user->can(CourseMember::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(CourseMember::permission('create'));
    }

    public function update(User $user, CourseMember $courseMember): bool
    {
        return $user->can(CourseMember::permission('edit'));
    }

    public function delete(User $user, CourseMember $courseMember): bool
    {
        return $user->can(CourseMember::permission('delete'));
    }
}
