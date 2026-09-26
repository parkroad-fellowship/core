<?php

namespace App\Filament\Resources\Cohorts\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Cohorts\CohortResource;
use App\Models\Cohort;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCohort extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(Cohort::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Cohort::permission('view'));
    }
}
