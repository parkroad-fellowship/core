<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
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
        return $user->can(Refund::permission('viewAny'));
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->can(Refund::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Refund::permission('create'));
    }

    public function update(User $user, Refund $refund): bool
    {
        return $user->can(Refund::permission('edit'));
    }

    public function delete(User $user, Refund $refund): bool
    {
        return $user->can(Refund::permission('delete'));
    }
}
