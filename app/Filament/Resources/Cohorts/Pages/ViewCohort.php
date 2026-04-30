<?php

namespace App\Filament\Resources\Cohorts\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Cohorts\CohortResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCohort extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

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
