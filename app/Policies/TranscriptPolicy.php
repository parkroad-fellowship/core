<?php

namespace App\Policies;

use App\Models\Transcript;
use App\Models\User;

class TranscriptPolicy
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
        return $user->can(Transcript::permission('viewAny'));
    }

    public function view(User $user, Transcript $transcript): bool
    {
        return $user->can(Transcript::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Transcript::permission('create'));
    }

    public function update(User $user, Transcript $transcript): bool
    {
        return $user->can(Transcript::permission('edit'));
    }

    public function delete(User $user, Transcript $transcript): bool
    {
        return $user->can(Transcript::permission('delete'));
    }
}
