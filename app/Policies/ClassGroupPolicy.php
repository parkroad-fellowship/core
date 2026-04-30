<?php

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\User;

class ClassGroupPolicy
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
        return $user->can(ClassGroup::permission('viewAny'));
    }

    public function view(User $user, ClassGroup $classGroup): bool
    {
        return $user->can(ClassGroup::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(ClassGroup::permission('create'));
    }

    public function update(User $user, ClassGroup $classGroup): bool
    {
        return $user->can(ClassGroup::permission('edit'));
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        return $user->can(ClassGroup::permission('delete'));
    }
}
