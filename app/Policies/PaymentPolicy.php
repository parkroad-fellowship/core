<?php

namespace App\Policies;

use App\Models\Payment;

class PaymentPolicy extends BasePolicy
{
    protected string $modelClass = Payment::class;
}
