<?php

namespace App\Policies;

use App\Models\DebriefNote;
use App\Models\User;

class DebriefNotePolicy
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
        return $user->can(DebriefNote::permission('viewAny'));
    }

    public function view(User $user, DebriefNote $debriefNote): bool
    {
        return $user->can(DebriefNote::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(DebriefNote::permission('create'));
    }

    public function update(User $user, DebriefNote $debriefNote): bool
    {
        return $user->can(DebriefNote::permission('edit'));
    }

    public function delete(User $user, DebriefNote $debriefNote): bool
    {
        return $user->can(DebriefNote::permission('delete'));
    }
}
