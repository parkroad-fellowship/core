<?php

namespace App\Policies;

use App\Models\MissionOfflineMember;
use App\Models\User;

class MissionOfflineMemberPolicy
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

    public function view(User $user, MissionOfflineMember $missionOfflineMember): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MissionOfflineMember $missionOfflineMember): bool
    {
        return true;
    }

    public function delete(User $user, MissionOfflineMember $missionOfflineMember): bool
    {
        return true;
    }
}
