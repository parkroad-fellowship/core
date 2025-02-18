<?php

namespace App\Filament\Resources\PRFEventResource\Pages;

use App\Filament\Resources\PRFEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPRFEvent extends EditRecord
{
    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(userCan('view event')),
            Actions\DeleteAction::make()->visible(userCan('delete event')),
            Actions\ForceDeleteAction::make()->visible(userCan('forceDelete event')),
            Actions\RestoreAction::make()->visible(userCan('restore event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit event');
    }
}
