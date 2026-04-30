<?php

namespace App\Policies;

use App\Models\MissionFaq;
use App\Models\User;

class MissionFaqPolicy
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
        return $user->can(MissionFaq::permission('viewAny'));
    }

    public function view(User $user, MissionFaq $missionFaq): bool
    {
        return $user->can(MissionFaq::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionFaq::permission('create'));
    }

    public function update(User $user, MissionFaq $missionFaq): bool
    {
        return $user->can(MissionFaq::permission('edit'));
    }

    public function delete(User $user, MissionFaq $missionFaq): bool
    {
        return $user->can(MissionFaq::permission('delete'));
    }
}
