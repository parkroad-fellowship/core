<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
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
        return $user->can(Letter::permission('viewAny'));
    }

    public function view(User $user, Letter $letter): bool
    {
        return $user->can(Letter::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Letter::permission('create'));
    }

    public function update(User $user, Letter $letter): bool
    {
        return $user->can(Letter::permission('edit'));
    }

    public function delete(User $user, Letter $letter): bool
    {
        return $user->can(Letter::permission('delete'));
    }

    public function restore(User $user, Letter $letter): bool
    {
        return $user->can(Letter::permission('restore'));
    }

    public function forceDelete(User $user, Letter $letter): bool
    {
        return $user->can(Letter::permission('forceDelete'));
    }
}
