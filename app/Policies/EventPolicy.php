<?php

namespace App\Policies;

use App\Models\PRFEvent;
use App\Models\User;

class EventPolicy
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
        return $user->can(PRFEvent::permission('viewAny'));
    }

    public function view(User $user, PRFEvent $event): bool
    {
        return $user->can(PRFEvent::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PRFEvent::permission('create'));
    }

    public function update(User $user, PRFEvent $event): bool
    {
        return $user->can(PRFEvent::permission('edit'));
    }

    public function delete(User $user, PRFEvent $event): bool
    {
        return $user->can(PRFEvent::permission('delete'));
    }
}
