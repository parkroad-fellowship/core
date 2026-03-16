<?php

namespace App\Policies;

use App\Models\MissionQuestion;
use App\Models\User;

class MissionQuestionPolicy
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
        return $user->can(MissionQuestion::permission('viewAny'));
    }

    public function view(User $user, MissionQuestion $missionQuestion): bool
    {
        return $user->can(MissionQuestion::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionQuestion::permission('create'));
    }

    public function update(User $user, MissionQuestion $missionQuestion): bool
    {
        return $user->can(MissionQuestion::permission('edit'));
    }

    public function delete(User $user, MissionQuestion $missionQuestion): bool
    {
        return $user->can(MissionQuestion::permission('delete'));
    }
}
