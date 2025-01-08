<?php

namespace App\Filament\Resources\TransferRateResource\Pages;

use App\Filament\Resources\TransferRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransferRate extends ViewRecord
{
    protected static string $resource = TransferRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => userCan('update transfer rate')),
        ];
    }
}
