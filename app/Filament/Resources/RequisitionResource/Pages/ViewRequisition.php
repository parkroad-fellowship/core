<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\RequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRequisition extends ViewRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit requisition')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view requisition');
    }
}
