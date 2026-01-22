<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\RequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view requisition')),
            DeleteAction::make()->visible(fn () => userCan('delete requisition')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete requisition')),
            RestoreAction::make()->visible(fn () => userCan('restore requisition')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit requisition');
    }
}
