<?php

namespace App\Policies;

use App\Models\Speaker;
use App\Models\User;

class SpeakerPolicy
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
        return $user->can(Speaker::permission('viewAny'));
    }

    public function view(User $user, Speaker $speaker): bool
    {
        return $user->can(Speaker::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Speaker::permission('create'));
    }

    public function update(User $user, Speaker $speaker): bool
    {
        return $user->can(Speaker::permission('edit'));
    }

    public function delete(User $user, Speaker $speaker): bool
    {
        return $user->can(Speaker::permission('delete'));
    }
}
