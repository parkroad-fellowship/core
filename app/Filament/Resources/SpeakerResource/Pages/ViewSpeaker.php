<?php

namespace App\Filament\Resources\SpeakerResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\SpeakerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSpeaker extends ViewRecord
{
    protected static string $resource = SpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(userCan('edit speaker')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view speaker');
    }
}
