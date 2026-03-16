<?php

namespace App\Policies;

use App\Models\ContactType;
use App\Models\User;

class ContactTypePolicy
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
        return $user->can(ContactType::permission('viewAny'));
    }

    public function view(User $user, ContactType $contactType): bool
    {
        return $user->can(ContactType::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(ContactType::permission('create'));
    }

    public function update(User $user, ContactType $contactType): bool
    {
        return $user->can(ContactType::permission('edit'));
    }

    public function delete(User $user, ContactType $contactType): bool
    {
        return $user->can(ContactType::permission('delete'));
    }
}
