<?php

namespace App\Policies;

use App\Models\EventSubscription;
use App\Models\User;

class EventSubscriptionPolicy
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
        return $user->can(EventSubscription::permission('viewAny'));
    }

    public function view(User $user, EventSubscription $eventSubscription): bool
    {
        return $user->can(EventSubscription::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(EventSubscription::permission('create'));
    }

    public function update(User $user, EventSubscription $eventSubscription): bool
    {
        return $user->can(EventSubscription::permission('edit'));
    }

    public function delete(User $user, EventSubscription $eventSubscription): bool
    {
        return $user->can(EventSubscription::permission('delete'));
    }
}
