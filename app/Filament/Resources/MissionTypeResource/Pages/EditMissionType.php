<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use App\Filament\Resources\MissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionType extends EditRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view mission type')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete mission type')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission type')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore  mission')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission type');
    }
}
