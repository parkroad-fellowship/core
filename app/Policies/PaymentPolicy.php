<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
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
        return $user->can(Payment::permission('viewAny'));
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can(Payment::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Payment::permission('create'));
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can(Payment::permission('edit'));
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can(Payment::permission('delete'));
    }
}
