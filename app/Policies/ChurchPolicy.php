<?php

namespace App\Policies;

use App\Models\Church;
use App\Models\User;

class ChurchPolicy
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
        return $user->can(Church::permission('viewAny'));
    }

    public function view(User $user, Church $church): bool
    {
        return $user->can(Church::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Church::permission('create'));
    }

    public function update(User $user, Church $church): bool
    {
        return $user->can(Church::permission('edit'));
    }

    public function delete(User $user, Church $church): bool
    {
        return $user->can(Church::permission('delete'));
    }
}
