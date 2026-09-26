<?php

namespace App\Filament\Resources\Speakers\Pages;

use App\Filament\Resources\Speakers\SpeakerResource;
use App\Models\Speaker;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSpeaker extends ViewRecord
{
    protected static string $resource = SpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(userCan(Speaker::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Speaker::permission('view'));
    }
}
