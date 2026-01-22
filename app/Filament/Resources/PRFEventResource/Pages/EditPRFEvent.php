<?php

namespace App\Filament\Resources\PRFEventResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\PRFEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPRFEvent extends EditRecord
{
    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(userCan('view event')),
            DeleteAction::make()->visible(userCan('delete event')),
            ForceDeleteAction::make()->visible(userCan('forceDelete event')),
            RestoreAction::make()->visible(userCan('restore event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit event');
    }
}
