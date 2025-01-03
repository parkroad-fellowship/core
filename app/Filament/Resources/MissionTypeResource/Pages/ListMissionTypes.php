<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use App\Filament\Resources\MissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissionTypes extends ListRecords
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => userCan('create mission type')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission type');
    }
}
