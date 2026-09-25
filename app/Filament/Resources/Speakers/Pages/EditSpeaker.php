<?php

namespace App\Filament\Resources\Speakers\Pages;

use App\Filament\Resources\Speakers\SpeakerResource;
use App\Models\Speaker;
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
            ViewAction::make()->visible(userCan(Speaker::permission('view'))),
            DeleteAction::make()->visible(userCan(Speaker::permission('delete'))),
            ForceDeleteAction::make()->visible(userCan(Speaker::permission('forceDelete'))),
            RestoreAction::make()->visible(userCan(Speaker::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Speaker::permission('edit'));
    }
}
