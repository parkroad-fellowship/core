<?php

namespace App\Policies;

use App\Models\PRFEventHandler;
use App\Models\User;

class PRFEventHandlerPolicy
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
        return $user->can(PRFEventHandler::permission('viewAny'));
    }

    public function view(User $user, PRFEventHandler $prfEventHandler): bool
    {
        return $user->can(PRFEventHandler::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PRFEventHandler::permission('create'));
    }

    public function update(User $user, PRFEventHandler $prfEventHandler): bool
    {
        return $user->can(PRFEventHandler::permission('edit'));
    }

    public function delete(User $user, PRFEventHandler $prfEventHandler): bool
    {
        return $user->can(PRFEventHandler::permission('delete'));
    }
}
