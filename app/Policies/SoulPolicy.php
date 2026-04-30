<?php

namespace App\Policies;

use App\Models\Soul;
use App\Models\User;

class SoulPolicy
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
        return $user->can(Soul::permission('viewAny'));
    }

    public function view(User $user, Soul $soul): bool
    {
        return $user->can(Soul::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Soul::permission('create'));
    }

    public function update(User $user, Soul $soul): bool
    {
        return $user->can(Soul::permission('edit'));
    }

    public function delete(User $user, Soul $soul): bool
    {
        return $user->can(Soul::permission('delete'));
    }
}
