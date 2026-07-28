<?php

namespace App\Filament\Central\Resources\CentralSettingResource\Pages;

use App\Filament\Central\Resources\CentralSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCentralSetting extends EditRecord
{
    protected static string $resource = CentralSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
