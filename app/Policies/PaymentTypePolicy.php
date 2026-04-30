<?php

namespace App\Policies;

use App\Models\PaymentType;
use App\Models\User;

class PaymentTypePolicy
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
        return $user->can(PaymentType::permission('viewAny'));
    }

    public function view(User $user, PaymentType $paymentType): bool
    {
        return $user->can(PaymentType::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PaymentType::permission('create'));
    }

    public function update(User $user, PaymentType $paymentType): bool
    {
        return $user->can(PaymentType::permission('edit'));
    }

    public function delete(User $user, PaymentType $paymentType): bool
    {
        return $user->can(PaymentType::permission('delete'));
    }
}
