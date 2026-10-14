<?php

namespace App\Filament\Resources\Requisitions\Pages;

use App\Filament\Resources\Requisitions\RequisitionResource;
use App\Models\Requisition;
use Filament\Resources\Pages\CreateRecord;

class CreateRequisition extends CreateRecord
{
    protected static string $resource = RequisitionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Requisition::permission('create'));
    }
}
