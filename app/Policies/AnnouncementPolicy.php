<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
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
        return $user->can(Announcement::permission('viewAny'));
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->can(Announcement::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Announcement::permission('create'));
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->can(Announcement::permission('edit'));
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->can(Announcement::permission('delete'));
    }
}
