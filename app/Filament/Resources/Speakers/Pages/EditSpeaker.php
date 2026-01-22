<?php

namespace App\Filament\Resources\Speakers\Pages;

use App\Filament\Resources\Speakers\SpeakerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSpeaker extends EditRecord
{
    protected static string $resource = SpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(userCan('view speaker')),
            DeleteAction::make()->visible(userCan('delete speaker')),
            ForceDeleteAction::make()->visible(userCan('forceDelete speaker')),
            RestoreAction::make()->visible(userCan('restore speaker')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit speaker');
    }
}
