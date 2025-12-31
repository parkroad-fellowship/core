<?php

namespace App\Observers;

use App\Enums\PRFMorphType;
use App\Jobs\School\CalculateRouteJob;
use App\Models\BudgetEstimate;
use App\Models\School;

class SchoolObserver
{
    /**
     * Handle the School "created" event.
     */
    public function created(School $school): void
    {
        CalculateRouteJob::dispatch($school);

        // Create a default budget estimate for the school
        BudgetEstimate::create([
            'budget_estimatable_id' => $school->id,
            'budget_estimatable_type' => PRFMorphType::SCHOOL,
            'grand_total' => 0,
        ]);
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
