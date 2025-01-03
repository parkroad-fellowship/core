<?php

namespace App\Filament\Resources\MissionResource\Pages;

use App\Filament\Resources\MissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMission extends EditRecord
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view mission')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete mission')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore mission')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission');
    }
}
