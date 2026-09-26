<?php

namespace App\Filament\Resources\MissionTypes\Pages;

use App\Filament\Resources\MissionTypes\MissionTypeResource;
use App\Models\MissionType;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionType extends ViewRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(MissionType::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionType::permission('view'));
    }
}
