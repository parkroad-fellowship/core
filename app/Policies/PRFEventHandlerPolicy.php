<?php

namespace App\Policies;

use App\Models\PRFEventHandler;

class PRFEventHandlerPolicy extends BasePolicy
{
    protected string $modelClass = PRFEventHandler::class;
}
