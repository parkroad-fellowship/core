<?php

namespace App\Filament\Resources\TransferRates\Pages;

use App\Filament\Resources\TransferRates\TransferRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransferRate extends CreateRecord
{
    protected static string $resource = TransferRateResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create transfer rate');
    }
}
