<?php

namespace App\Filament\Resources\AccountingEvents\Pages;

use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountingEvent extends EditRecord
{
    protected static string $resource = AccountingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(userCan('view accounting event')),
            DeleteAction::make()->visible(userCan('delete accounting event')),
            ForceDeleteAction::make()->visible(userCan('force delete accounting event')),
            RestoreAction::make()->visible(userCan('restore accounting event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit accounting event');
    }
}
