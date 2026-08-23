<?php

namespace App\Policies;

use App\Models\Profession;

class ProfessionPolicy extends BasePolicy
{
    protected string $modelClass = Profession::class;
}
