<?php

namespace App\Policies;

use App\Models\Requisition;
use App\Models\User;

class RequisitionPolicy extends BasePolicy
{
    protected string $modelClass = Requisition::class;

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
