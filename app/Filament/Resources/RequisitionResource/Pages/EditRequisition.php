<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Filament\Resources\RequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view requisition')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete requisition')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete requisition')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore requisition')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit requisition');
    }
}
