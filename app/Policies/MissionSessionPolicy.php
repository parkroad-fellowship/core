<?php

namespace App\Policies;

use App\Models\MissionSession;
use App\Models\User;

class MissionSessionPolicy
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
        return $user->can(MissionSession::permission('viewAny'));
    }

    public function view(User $user, MissionSession $missionSession): bool
    {
        return $user->can(MissionSession::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionSession::permission('create'));
    }

    public function update(User $user, MissionSession $missionSession): bool
    {
        return $user->can(MissionSession::permission('edit'));
    }

    public function delete(User $user, MissionSession $missionSession): bool
    {
        return $user->can(MissionSession::permission('delete'));
    }
}
