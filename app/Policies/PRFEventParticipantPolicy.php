<?php

namespace App\Policies;

use App\Models\PRFEventParticipant;
use App\Models\User;

class PRFEventParticipantPolicy
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
        return $user->can(PRFEventParticipant::permission('viewAny'));
    }

    public function view(User $user, PRFEventParticipant $prfEventParticipant): bool
    {
        return $user->can(PRFEventParticipant::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PRFEventParticipant::permission('create'));
    }

    public function update(User $user, PRFEventParticipant $prfEventParticipant): bool
    {
        return $user->can(PRFEventParticipant::permission('edit'));
    }

    public function delete(User $user, PRFEventParticipant $prfEventParticipant): bool
    {
        return $user->can(PRFEventParticipant::permission('delete'));
    }
}
