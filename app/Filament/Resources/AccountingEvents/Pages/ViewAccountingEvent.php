<?php

namespace App\Filament\Resources\AccountingEvents\Pages;

use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountingEvent extends ViewRecord
{
    protected static string $resource = AccountingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(userCan('edit accounting event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view accounting event');
    }
}
