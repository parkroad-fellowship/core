<?php

namespace App\Filament\Resources\SpeakerResource\Pages;

use App\Filament\Resources\SpeakerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpeaker extends EditRecord
{
    protected static string $resource = SpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(userCan('view speaker')),
            Actions\DeleteAction::make()->visible(userCan('delete speaker')),
            Actions\ForceDeleteAction::make()->visible(userCan('forceDelete speaker')),
            Actions\RestoreAction::make()->visible(userCan('restore speaker')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit speaker');
    }
}
