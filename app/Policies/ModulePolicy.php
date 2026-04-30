<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
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
        return $user->can(Module::permission('viewAny'));
    }

    public function view(User $user, Module $module): bool
    {
        return $user->can(Module::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Module::permission('create'));
    }

    public function update(User $user, Module $module): bool
    {
        return $user->can(Module::permission('edit'));
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->can(Module::permission('delete'));
    }
}
