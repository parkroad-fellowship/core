<?php

namespace App\Filament\Resources\MissionGroundSuggestions\Pages;

use App\Filament\Resources\MissionGroundSuggestions\MissionGroundSuggestionResource;
use App\Models\MissionGroundSuggestion;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionGroundSuggestion extends ViewRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(MissionGroundSuggestion::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionGroundSuggestion::permission('view'));
    }
}
