<?php

namespace App\Policies;

use App\Models\SpiritualYear;
use App\Models\User;

class SpiritualYearPolicy
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
        return $user->can(SpiritualYear::permission('viewAny'));
    }

    public function view(User $user, SpiritualYear $spiritualYear): bool
    {
        return $user->can(SpiritualYear::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(SpiritualYear::permission('create'));
    }

    public function update(User $user, SpiritualYear $spiritualYear): bool
    {
        return $user->can(SpiritualYear::permission('edit'));
    }

    public function delete(User $user, SpiritualYear $spiritualYear): bool
    {
        return $user->can(SpiritualYear::permission('delete'));
    }
}
