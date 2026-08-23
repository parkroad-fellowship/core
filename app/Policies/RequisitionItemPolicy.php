<?php

namespace App\Policies;

use App\Models\RequisitionItem;

class RequisitionItemPolicy extends BasePolicy
{
    protected string $modelClass = RequisitionItem::class;
}
