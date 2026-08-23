<?php

namespace App\Policies;

use App\Models\Module;

class ModulePolicy extends BasePolicy
{
    protected string $modelClass = Module::class;
}
