<?php

namespace App\Filament\Resources\AccountingEventResource\Pages;

use App\Filament\Resources\AccountingEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountingEvent extends ViewRecord
{
    protected static string $resource = AccountingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(userCan('edit accounting event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view accounting event');
    }
}
