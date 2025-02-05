<?php

namespace App\Filament\Resources\MissionGroundSuggestionResource\Pages;

use App\Filament\Resources\MissionGroundSuggestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionGroundSuggestion extends CreateRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission ground suggestion');
    }
}
