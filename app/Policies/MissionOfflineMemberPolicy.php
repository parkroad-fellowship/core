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
        return $user->can(MissionOfflineMember::permission('viewAny'));
    }

    public function view(User $user, MissionOfflineMember $missionOfflineMember): bool
    {
        return $user->can(MissionOfflineMember::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionOfflineMember::permission('create'));
    }

    public function update(User $user, MissionOfflineMember $missionOfflineMember): bool
    {
        return $user->can(MissionOfflineMember::permission('edit'));
    }

    public function delete(User $user, MissionOfflineMember $missionOfflineMember): bool
    {
        return $user->can(MissionOfflineMember::permission('delete'));
    }
}
