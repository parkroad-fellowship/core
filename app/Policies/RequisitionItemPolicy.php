<?php

namespace App\Policies;

use App\Models\RequisitionItem;
use App\Models\User;

class RequisitionItemPolicy
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
        return $user->can(RequisitionItem::permission('viewAny'));
    }

    public function view(User $user, RequisitionItem $requisitionItem): bool
    {
        return $user->can(RequisitionItem::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(RequisitionItem::permission('create'));
    }

    public function update(User $user, RequisitionItem $requisitionItem): bool
    {
        return $user->can(RequisitionItem::permission('edit'));
    }

    public function delete(User $user, RequisitionItem $requisitionItem): bool
    {
        return $user->can(RequisitionItem::permission('delete'));
    }
}
