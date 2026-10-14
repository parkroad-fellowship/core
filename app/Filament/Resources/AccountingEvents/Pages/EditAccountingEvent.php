<?php

namespace App\Filament\Resources\AccountingEvents\Pages;

use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use App\Models\AccountingEvent;
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
            ViewAction::make()->visible(userCan(AccountingEvent::permission('view'))),
            DeleteAction::make()->visible(userCan(AccountingEvent::permission('delete'))),
            ForceDeleteAction::make()->visible(userCan(AccountingEvent::permission('forceDelete'))),
            RestoreAction::make()->visible(userCan(AccountingEvent::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(AccountingEvent::permission('edit'));
    }
}
