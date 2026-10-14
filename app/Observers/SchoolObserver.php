<?php

namespace App\Observers;

use App\Events\School\SchoolLocationChanged;
use App\Models\School;

/**
 * Translates School lifecycle changes into domain events; side effects live in listeners.
 */
class SchoolObserver
{
    public function created(School $school): void
    {
        SchoolLocationChanged::dispatch($school);
    }

    public function updated(School $school): void
    {
        if ($school->wasChanged(['latitude', 'longitude'])) {
            SchoolLocationChanged::dispatch($school);
        }
    }
}
