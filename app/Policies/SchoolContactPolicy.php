<?php

namespace App\Policies;

use App\Models\SchoolContact;
use App\Models\User;

class SchoolContactPolicy
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
        return $user->can(SchoolContact::permission('viewAny'));
    }

    public function view(User $user, SchoolContact $schoolContact): bool
    {
        return $user->can(SchoolContact::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(SchoolContact::permission('create'));
    }

    public function update(User $user, SchoolContact $schoolContact): bool
    {
        return $user->can(SchoolContact::permission('edit'));
    }

    public function delete(User $user, SchoolContact $schoolContact): bool
    {
        return $user->can(SchoolContact::permission('delete'));
    }
}
