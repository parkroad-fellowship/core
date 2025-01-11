<?php

namespace App\Filament\Resources\TransferRateResource\Pages;

use App\Filament\Resources\TransferRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransferRates extends ListRecords
{
    protected static string $resource = TransferRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => userCan('create transfer rate')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny transfer rate');
    }
}
