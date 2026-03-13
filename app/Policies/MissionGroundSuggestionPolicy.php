<?php

namespace App\Policies;

use App\Models\MissionGroundSuggestion;
use App\Models\User;

class MissionGroundSuggestionPolicy
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
        return $user->can(MissionGroundSuggestion::permission('viewAny'));
    }

    public function view(User $user, MissionGroundSuggestion $missionGroundSuggestion): bool
    {
        return $user->can(MissionGroundSuggestion::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionGroundSuggestion::permission('create'));
    }

    public function update(User $user, MissionGroundSuggestion $missionGroundSuggestion): bool
    {
        return $user->can(MissionGroundSuggestion::permission('edit'));
    }

    public function delete(User $user, MissionGroundSuggestion $missionGroundSuggestion): bool
    {
        return $user->can(MissionGroundSuggestion::permission('delete'));
    }
}
