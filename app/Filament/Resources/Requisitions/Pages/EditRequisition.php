<?php

namespace App\Filament\Resources\Requisitions\Pages;

use App\Filament\Resources\Requisitions\RequisitionResource;
use App\Models\Requisition;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Requisition::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Requisition::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Requisition::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Requisition::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Requisition::permission('edit'));
    }
}
