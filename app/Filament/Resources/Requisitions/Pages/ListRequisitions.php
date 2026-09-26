<?php

namespace App\Filament\Resources\Requisitions\Pages;

use App\Filament\Resources\Requisitions\RequisitionResource;
use App\Models\Requisition;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRequisitions extends ListRecords
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(Requisition::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return false;
        return userCan(Requisition::permission('viewAny'));
    }
}
