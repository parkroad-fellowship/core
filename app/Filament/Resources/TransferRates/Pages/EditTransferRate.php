<?php

namespace App\Filament\Resources\TransferRates\Pages;

use App\Filament\Resources\TransferRates\TransferRateResource;
use App\Models\TransferRate;
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
            ViewAction::make()->visible(fn() => userCan(TransferRate::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(TransferRate::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(TransferRate::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(TransferRate::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(TransferRate::permission('edit'));
    }
}
