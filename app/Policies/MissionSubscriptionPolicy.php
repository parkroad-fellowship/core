<?php

namespace App\Policies;

use App\Models\MissionSubscription;
use App\Models\User;

class MissionSubscriptionPolicy
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
        return $user->can(MissionSubscription::permission('viewAny'));
    }

    public function view(User $user, MissionSubscription $missionSubscription): bool
    {
        return $user->can(MissionSubscription::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionSubscription::permission('create'));
    }

    public function update(User $user, MissionSubscription $missionSubscription): bool
    {
        return $user->can(MissionSubscription::permission('edit'));
    }

    public function delete(User $user, MissionSubscription $missionSubscription): bool
    {
        return $user->can(MissionSubscription::permission('delete'));
    }
}
