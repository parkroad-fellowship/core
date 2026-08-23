<?php

namespace App\Policies;

use App\Models\Transcript;

class TranscriptPolicy extends BasePolicy
{
    protected string $modelClass = Transcript::class;
}
