<?php

namespace App\Filament\Resources\TransferRates\Pages;

use App\Filament\Resources\TransferRates\TransferRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransferRates extends ListRecords
{
    protected static string $resource = TransferRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create transfer rate')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny transfer rate');
    }
}
