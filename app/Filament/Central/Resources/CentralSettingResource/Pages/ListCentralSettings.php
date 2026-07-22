<?php

namespace App\Filament\Central\Resources\CentralSettingResource\Pages;

use App\Filament\Central\Resources\CentralSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCentralSettings extends ListRecords
{
    protected static string $resource = CentralSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
