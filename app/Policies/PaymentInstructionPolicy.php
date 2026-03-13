<?php

namespace App\Policies;

use App\Models\PaymentInstruction;
use App\Models\User;

class PaymentInstructionPolicy
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
        return $user->can(PaymentInstruction::permission('viewAny'));
    }

    public function view(User $user, PaymentInstruction $paymentInstruction): bool
    {
        return $user->can(PaymentInstruction::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PaymentInstruction::permission('create'));
    }

    public function update(User $user, PaymentInstruction $paymentInstruction): bool
    {
        return $user->can(PaymentInstruction::permission('edit'));
    }

    public function delete(User $user, PaymentInstruction $paymentInstruction): bool
    {
        return $user->can(PaymentInstruction::permission('delete'));
    }
}
