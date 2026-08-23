<?php

namespace App\Policies;

use App\Models\SchoolContact;

class SchoolContactPolicy extends BasePolicy
{
    protected string $modelClass = SchoolContact::class;
}
