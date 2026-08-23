<?php

namespace App\Policies;

use App\Models\DebriefNote;

class DebriefNotePolicy extends BasePolicy
{
    protected string $modelClass = DebriefNote::class;
}
