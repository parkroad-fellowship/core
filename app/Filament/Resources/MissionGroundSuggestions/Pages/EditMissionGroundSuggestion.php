<?php

namespace App\Filament\Resources\MissionGroundSuggestions\Pages;

use App\Filament\Resources\MissionGroundSuggestions\MissionGroundSuggestionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMissionGroundSuggestion extends EditRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view mission ground suggestion')),
            DeleteAction::make()->visible(fn () => userCan('delete mission ground suggestion')),
            ForceDeleteAction::make()->visible(fn () => userCan('force delete mission ground suggestion')),
            RestoreAction::make()->visible(fn () => userCan('restore mission ground suggestion')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission ground suggestion');
    }
}
