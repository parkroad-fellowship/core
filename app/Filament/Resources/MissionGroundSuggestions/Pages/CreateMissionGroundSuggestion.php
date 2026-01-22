<?php

namespace App\Filament\Resources\MissionGroundSuggestions\Pages;

use App\Filament\Resources\MissionGroundSuggestions\MissionGroundSuggestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionGroundSuggestion extends CreateRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission ground suggestion');
    }
}
