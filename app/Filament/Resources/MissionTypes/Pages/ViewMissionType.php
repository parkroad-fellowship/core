<?php

namespace App\Filament\Resources\MissionTypes\Pages;

use App\Filament\Resources\MissionTypes\MissionTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionType extends ViewRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [

            EditAction::make()->visible(fn () => userCan('edit mission type')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission type');
    }
}
