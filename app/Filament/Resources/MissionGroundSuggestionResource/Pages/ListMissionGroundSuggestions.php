<?php

namespace App\Filament\Resources\MissionGroundSuggestionResource\Pages;

use App\Filament\Resources\MissionGroundSuggestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissionGroundSuggestions extends ListRecords
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => userCan('create mission ground suggestion')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission ground suggestion');
    }
}
