<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\User;

class MissionPolicy
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

    public function view(User $user, Mission $mission): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('missions secretary');
    }

    public function update(User $user, Mission $mission): bool
    {
        return $user->hasRole('missions secretary');
    }

    public function delete(User $user, Mission $mission): bool
    {
        return $user->hasRole('missions secretary');
    }
}
