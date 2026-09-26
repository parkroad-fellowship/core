<?php

namespace App\Filament\Resources\LedgerEntries\Pages;

use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Models\LedgerEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewLedgerEntry extends ViewRecord
{
    protected static string $resource = LedgerEntryResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerEntry::permission('view'));
    }
}
