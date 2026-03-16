<?php

namespace App\Policies;

use App\Models\MemberModule;
use App\Models\User;

class MemberModulePolicy
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
        return $user->can(MemberModule::permission('viewAny'));
    }

    public function view(User $user, MemberModule $memberModule): bool
    {
        return $user->can(MemberModule::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(MemberModule::permission('create'));
    }

    public function update(User $user, MemberModule $memberModule): bool
    {
        return $user->can(MemberModule::permission('edit'));
    }

    public function delete(User $user, MemberModule $memberModule): bool
    {
        return $user->can(MemberModule::permission('delete'));
    }
}
