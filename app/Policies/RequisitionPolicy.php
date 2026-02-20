<?php

namespace App\Policies;

use App\Enums\PRFApprovalStatus;
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
        return true;
    }

    public function view(User $user, Requisition $requisition): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Requisition $requisition): bool
    {
        $isOwner = $user->member?->id === $requisition->member_id;
        $isTreasurer = $user->hasRole('treasurer');

        return $isOwner || $isTreasurer;
    }

    public function delete(User $user, Requisition $requisition): bool
    {
        $isOwner = $user->member?->id === $requisition->member_id;
        $isPending = $requisition->approval_status === PRFApprovalStatus::PENDING->value
            || $requisition->approval_status === null;

        return $isOwner && $isPending;
    }

    public function approve(User $user, Requisition $requisition): bool
    {
        $isTreasurerOrChairperson = $user->hasAnyRole(['treasurer', 'chairperson']);
        $isNotOwner = $user->member?->id !== $requisition->member_id;

        return $isTreasurerOrChairperson && $isNotOwner;
    }

    public function reject(User $user, Requisition $requisition): bool
    {
        return $user->hasAnyRole(['treasurer', 'chairperson']);
    }

    public function recall(User $user, Requisition $requisition): bool
    {
        $isOwner = $user->member?->id === $requisition->member_id;
        $isTreasurer = $user->hasRole('treasurer');

        return $isOwner || $isTreasurer;
    }
}
