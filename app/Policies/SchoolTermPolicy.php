<?php

namespace App\Policies;

use App\Models\SchoolTerm;
use App\Models\User;

class SchoolTermPolicy
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
        return $user->can(SchoolTerm::permission('viewAny'));
    }

    public function view(User $user, SchoolTerm $schoolTerm): bool
    {
        return $user->can(SchoolTerm::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(SchoolTerm::permission('create'));
    }

    public function update(User $user, SchoolTerm $schoolTerm): bool
    {
        return $user->can(SchoolTerm::permission('edit'));
    }

    public function delete(User $user, SchoolTerm $schoolTerm): bool
    {
        return $user->can(SchoolTerm::permission('delete'));
    }
}
