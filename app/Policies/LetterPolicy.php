<?php

namespace App\Policies;

use App\Models\Letter;

class LetterPolicy extends BasePolicy
{
    protected string $modelClass = Letter::class;
}
