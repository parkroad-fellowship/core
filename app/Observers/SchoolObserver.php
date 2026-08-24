<?php

namespace App\Observers;

use App\Jobs\School\CalculateRouteJob;
use App\Models\School;

class SchoolObserver
{
    /**
     * Handle the School "created" event.
     */
    public function created(School $school): void
    {
        CalculateRouteJob::dispatch($school);
    }

    /**
     * Handle the School "updated" event.
     */
    public function updated(School $school): void
    {
        if ($school->wasChanged('latitude') || $school->wasChanged('longitude')) {
            CalculateRouteJob::dispatch($school);
        }
    }

    /**
     * Handle the School "deleted" event.
     */
    public function deleted(School $school): void
    {
        //
    }

    /**
     * Handle the School "restored" event.
     */
    public function restored(School $school): void
    {
        //
    }

    /**
     * Handle the School "force deleted" event.
     */
    public function forceDeleted(School $school): void
    {
        //
    }
}
