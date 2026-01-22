<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\MissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionType extends EditRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view mission type')),
            DeleteAction::make()->visible(fn () => userCan('delete mission type')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission type')),
            RestoreAction::make()->visible(fn () => userCan('restore  mission')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission type');
    }
}
