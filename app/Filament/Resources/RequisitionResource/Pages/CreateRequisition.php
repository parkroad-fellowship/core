<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Filament\Resources\RequisitionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequisition extends CreateRecord
{
    protected static string $resource = RequisitionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create requisition');
    }
}
