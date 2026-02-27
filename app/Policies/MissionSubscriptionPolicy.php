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
        return true;
    }

    public function view(User $user, MissionSubscription $missionSubscription): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MissionSubscription $missionSubscription): bool
    {
        return $user->member?->id === $missionSubscription->member_id
            || $user->hasRole('missions secretary');
    }

    public function delete(User $user, MissionSubscription $missionSubscription): bool
    {
        return $user->member?->id === $missionSubscription->member_id
            || $user->hasRole('missions secretary');
    }
}
