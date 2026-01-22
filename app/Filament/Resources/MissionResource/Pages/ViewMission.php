<?php

namespace App\Filament\Resources\MissionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\MissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMission extends ViewRecord
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit mission')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission');
    }
}
