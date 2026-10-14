<?php

namespace App\Filament\Resources\Cohorts\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Cohorts\CohortResource;
use App\Models\Cohort;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCohort extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Cohort::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Cohort::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Cohort::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Cohort::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Cohort::permission('edit'));
    }
}
