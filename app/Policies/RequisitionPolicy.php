<?php

namespace App\Policies;

use App\Models\Requisition;
use App\Models\User;

class RequisitionPolicy
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
        return $user->can(Requisition::permission('viewAny'));
    }

    public function view(User $user, Requisition $requisition): bool
    {
        return $user->can(Requisition::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Requisition::permission('create'));
    }

    public function update(User $user, Requisition $requisition): bool
    {
        return $user->can(Requisition::permission('edit'));
    }

    public function delete(User $user, Requisition $requisition): bool
    {
        return $user->can(Requisition::permission('delete'));
    }

    public function approve(User $user, Requisition $requisition): bool
    {
        return $user->can(Requisition::permission('approve'));
    }

    public function reject(User $user, Requisition $requisition): bool
    {
        return $user->can(Requisition::permission('reject'));
    }

    public function recall(User $user, Requisition $requisition): bool
    {
        return $user->can(Requisition::permission('recall'));
    }
}
