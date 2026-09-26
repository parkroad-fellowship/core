<?php

namespace App\Events\School;

use App\Models\School;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A school was added or its coordinates changed.
 */
class SchoolLocationChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public School $school,
    ) {}
}
