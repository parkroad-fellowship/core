<?php

namespace App\Filament\Resources\TransferRateResource\Pages;

use App\Filament\Resources\TransferRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransferRate extends EditRecord
{
    protected static string $resource = TransferRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view transfer rate')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete transfer rate')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete transfer rate')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore transfer rate')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit transfer rate');
    }
}
