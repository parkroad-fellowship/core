<?php

namespace App\Filament\Resources\AccountingEvents\Pages;

use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use App\Models\AccountingEvent;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountingEvent extends CreateRecord
{
    protected static string $resource = AccountingEventResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(AccountingEvent::permission('create'));
    }
}
