<?php

namespace App\Filament\Resources\AccountingEventResource\Pages;

use App\Filament\Resources\AccountingEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingEvent extends EditRecord
{
    protected static string $resource = AccountingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(userCan('view accounting event')),
            Actions\DeleteAction::make()->visible(userCan('delete accounting event')),
            Actions\ForceDeleteAction::make()->visible(userCan('force delete accounting event')),
            Actions\RestoreAction::make()->visible(userCan('restore accounting event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit accounting event');
    }
}
