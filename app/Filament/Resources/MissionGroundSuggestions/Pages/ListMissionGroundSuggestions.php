<?php

namespace App\Filament\Resources\MissionGroundSuggestions\Pages;

use App\Filament\Resources\MissionGroundSuggestions\MissionGroundSuggestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMissionGroundSuggestions extends ListRecords
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create mission ground suggestion')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission ground suggestion');
    }
}
