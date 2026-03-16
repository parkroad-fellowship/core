<?php

namespace App\Policies;

use App\Models\AccountingEvent;
use App\Models\User;

class AccountingEventPolicy
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
        return $user->can(AccountingEvent::permission('viewAny'));
    }

    public function view(User $user, AccountingEvent $accountingEvent): bool
    {
        return $user->can(AccountingEvent::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(AccountingEvent::permission('create'));
    }

    public function update(User $user, AccountingEvent $accountingEvent): bool
    {
        return $user->can(AccountingEvent::permission('edit'));
    }

    public function delete(User $user, AccountingEvent $accountingEvent): bool
    {
        return $user->can(AccountingEvent::permission('delete'));
    }
}
