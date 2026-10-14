<?php

namespace App\Filament\Resources\Cohorts\Pages;

use App\Filament\Resources\Cohorts\CohortResource;
use App\Models\Cohort;
use Filament\Resources\Pages\CreateRecord;

class CreateCohort extends CreateRecord
{
    protected static string $resource = CohortResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Cohort::permission('create'));
    }
}
