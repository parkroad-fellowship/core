<?php

namespace App\Filament\Resources\Cohorts\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Cohorts\CohortResource;
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
            ViewAction::make()->visible(fn () => userCan('view cohort')),
            DeleteAction::make()->visible(fn () => userCan('delete cohort')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete cohort')),
            RestoreAction::make()->visible(fn () => userCan('restore cohort')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit cohort');
    }
}
