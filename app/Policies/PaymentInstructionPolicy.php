<?php

namespace App\Policies;

use App\Models\PaymentInstruction;

class PaymentInstructionPolicy extends BasePolicy
{
    protected string $modelClass = PaymentInstruction::class;
}
