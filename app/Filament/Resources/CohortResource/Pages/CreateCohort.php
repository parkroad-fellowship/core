<?php

namespace App\Filament\Resources\CohortResource\Pages;

use App\Filament\Resources\CohortResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCohort extends CreateRecord
{
    protected static string $resource = CohortResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create cohort');
    }
}
