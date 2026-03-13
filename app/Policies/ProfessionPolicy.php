<?php

namespace App\Policies;

use App\Models\Profession;
use App\Models\User;

class ProfessionPolicy
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
        return $user->can(Profession::permission('viewAny'));
    }

    public function view(User $user, Profession $profession): bool
    {
        return $user->can(Profession::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Profession::permission('create'));
    }

    public function update(User $user, Profession $profession): bool
    {
        return $user->can(Profession::permission('edit'));
    }

    public function delete(User $user, Profession $profession): bool
    {
        return $user->can(Profession::permission('delete'));
    }
}
