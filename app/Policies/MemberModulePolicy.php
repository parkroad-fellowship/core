<?php

namespace App\Policies;

use App\Models\MemberModule;

class MemberModulePolicy extends BasePolicy
{
    protected string $modelClass = MemberModule::class;
}
