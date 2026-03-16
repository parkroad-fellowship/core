<?php

namespace App\Policies;

use App\Models\Gift;
use App\Models\User;

class GiftPolicy
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
        return $user->can(Gift::permission('viewAny'));
    }

    public function view(User $user, Gift $gift): bool
    {
        return $user->can(Gift::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Gift::permission('create'));
    }

    public function update(User $user, Gift $gift): bool
    {
        return $user->can(Gift::permission('edit'));
    }

    public function delete(User $user, Gift $gift): bool
    {
        return $user->can(Gift::permission('delete'));
    }
}
