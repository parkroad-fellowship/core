<?php

namespace App\Policies;

use App\Models\MaritalStatus;
use App\Models\User;

class MaritalStatusPolicy
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
        return $user->can(MaritalStatus::permission('viewAny'));
    }

    public function view(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can(MaritalStatus::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MaritalStatus::permission('create'));
    }

    public function update(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can(MaritalStatus::permission('edit'));
    }

    public function delete(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can(MaritalStatus::permission('delete'));
    }
}
