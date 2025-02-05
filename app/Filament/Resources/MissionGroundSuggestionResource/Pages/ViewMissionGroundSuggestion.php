<?php

namespace App\Filament\Resources\MissionGroundSuggestionResource\Pages;

use App\Filament\Resources\MissionGroundSuggestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionGroundSuggestion extends ViewRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => userCan('edit mission ground suggestion')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission ground suggestion');
    }
}
