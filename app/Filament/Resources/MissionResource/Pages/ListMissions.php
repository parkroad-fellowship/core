<?php

namespace App\Filament\Resources\MissionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissions extends ListRecords
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create mission')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission');
    }
}
