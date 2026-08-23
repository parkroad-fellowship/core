<?php

namespace App\Policies;

use App\Models\PaymentType;

class PaymentTypePolicy extends BasePolicy
{
    protected string $modelClass = PaymentType::class;
}
