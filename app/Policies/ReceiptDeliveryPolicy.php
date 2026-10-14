<?php

namespace App\Policies;

use App\Models\ReceiptDelivery;

class ReceiptDeliveryPolicy extends BasePolicy
{
    protected string $modelClass = ReceiptDelivery::class;
}
