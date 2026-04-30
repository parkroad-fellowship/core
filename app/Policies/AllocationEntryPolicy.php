<?php

namespace App\Policies;

use App\Models\AllocationEntry;
use App\Models\User;

class AllocationEntryPolicy
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
        return $user->can(AllocationEntry::permission('viewAny'));
    }

    public function view(User $user, AllocationEntry $allocationEntry): bool
    {
        return $user->can(AllocationEntry::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(AllocationEntry::permission('create'));
    }

    public function update(User $user, AllocationEntry $allocationEntry): bool
    {
        return $user->can(AllocationEntry::permission('edit'));
    }

    public function delete(User $user, AllocationEntry $allocationEntry): bool
    {
        return $user->can(AllocationEntry::permission('delete'));
    }
}
