<?php

namespace App\Filament\Resources\CohortResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\CohortResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCohort extends EditRecord
{
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
