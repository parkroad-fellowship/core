<?php

namespace App\Filament\Resources\CohortResource\Pages;

use App\Filament\Resources\CohortResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;



class EditCohort extends EditRecord
{
    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view cohort')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete cohort')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete cohort')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore cohort')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit cohort');
    }
}
