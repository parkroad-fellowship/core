<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
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
        return $user->can(Department::permission('viewAny'));
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can(Department::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Department::permission('create'));
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can(Department::permission('edit'));
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can(Department::permission('delete'));
    }
}
