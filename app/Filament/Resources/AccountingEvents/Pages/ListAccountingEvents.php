<?php

namespace App\Filament\Resources\AccountingEvents\Pages;

use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountingEvents extends ListRecords
{
    protected static string $resource = AccountingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(userCan('create accounting event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny accounting event');
    }
}
