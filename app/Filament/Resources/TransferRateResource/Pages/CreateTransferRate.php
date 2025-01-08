<?php

namespace App\Filament\Resources\TransferRateResource\Pages;

use App\Filament\Resources\TransferRateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransferRate extends CreateRecord
{
    protected static string $resource = TransferRateResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create transfer rate');
    }
}
