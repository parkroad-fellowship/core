<?php

namespace App\Filament\Resources\APIClients\Pages;

use App\Filament\Resources\APIClients\APIClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAPIClients extends ListRecords
{
    protected static string $resource = APIClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
