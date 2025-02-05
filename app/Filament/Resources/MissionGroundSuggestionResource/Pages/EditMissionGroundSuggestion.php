<?php

namespace App\Filament\Resources\MissionGroundSuggestionResource\Pages;

use App\Filament\Resources\MissionGroundSuggestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionGroundSuggestion extends EditRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view mission ground suggestion')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete mission ground suggestion')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('force delete mission ground suggestion')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore mission ground suggestion')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission ground suggestion');
    }
}
