<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use App\Filament\Resources\MissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionType extends ViewRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
