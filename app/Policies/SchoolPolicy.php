<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
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

    public function view(User $user, School $school): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('missions secretary');
    }

    public function update(User $user, School $school): bool
    {
        return $user->hasRole('missions secretary');
    }

    public function delete(User $user, School $school): bool
    {
        return $user->hasRole('missions secretary');
    }
}
