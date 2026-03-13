<?php

namespace App\Policies;

use App\Models\ConnectedAccount;
use App\Models\User;

class ConnectedAccountPolicy
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
        return $user->can(ConnectedAccount::permission('viewAny'));
    }

    public function view(User $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->can(ConnectedAccount::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(ConnectedAccount::permission('create'));
    }

    public function update(User $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->can(ConnectedAccount::permission('edit'));
    }

    public function delete(User $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->can(ConnectedAccount::permission('delete'));
    }
}
