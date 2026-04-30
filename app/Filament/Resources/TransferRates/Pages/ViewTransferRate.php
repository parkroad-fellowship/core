<?php

namespace App\Filament\Resources\TransferRates\Pages;

use App\Filament\Resources\TransferRates\TransferRateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransferRate extends ViewRecord
{
    protected static string $resource = TransferRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('update transfer rate')),
        ];
    }
}
