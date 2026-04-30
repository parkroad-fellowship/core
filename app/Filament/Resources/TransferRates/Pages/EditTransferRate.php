<?php

namespace App\Filament\Resources\TransferRates\Pages;

use App\Filament\Resources\TransferRates\TransferRateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTransferRate extends EditRecord
{
    protected static string $resource = TransferRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view transfer rate')),
            DeleteAction::make()->visible(fn () => userCan('delete transfer rate')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete transfer rate')),
            RestoreAction::make()->visible(fn () => userCan('restore transfer rate')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit transfer rate');
    }
}
