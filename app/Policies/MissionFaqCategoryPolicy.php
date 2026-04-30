<?php

namespace App\Policies;

use App\Models\MissionFaqCategory;
use App\Models\User;

class MissionFaqCategoryPolicy
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
        return $user->can(MissionFaqCategory::permission('viewAny'));
    }

    public function view(User $user, MissionFaqCategory $missionFaqCategory): bool
    {
        return $user->can(MissionFaqCategory::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MissionFaqCategory::permission('create'));
    }

    public function update(User $user, MissionFaqCategory $missionFaqCategory): bool
    {
        return $user->can(MissionFaqCategory::permission('edit'));
    }

    public function delete(User $user, MissionFaqCategory $missionFaqCategory): bool
    {
        return $user->can(MissionFaqCategory::permission('delete'));
    }
}
