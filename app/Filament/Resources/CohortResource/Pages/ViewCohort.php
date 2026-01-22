<?php

namespace App\Filament\Resources\CohortResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CohortResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCohort extends ViewRecord
{
    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit cohort')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view cohort');
    }
}
