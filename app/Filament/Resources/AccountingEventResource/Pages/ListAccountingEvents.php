<?php

namespace App\Filament\Resources\AccountingEventResource\Pages;

use App\Filament\Resources\AccountingEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingEvents extends ListRecords
{
    protected static string $resource = AccountingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(userCan('create accounting event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny accounting event');
    }
}
