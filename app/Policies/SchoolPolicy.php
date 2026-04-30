<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
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
        return $user->can(School::permission('viewAny'));
    }

    public function view(User $user, School $school): bool
    {
        return $user->can(School::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(School::permission('create'));
    }

    public function update(User $user, School $school): bool
    {
        return $user->can(School::permission('edit'));
    }

    public function delete(User $user, School $school): bool
    {
        return $user->can(School::permission('delete'));
    }
}
