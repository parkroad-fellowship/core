<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Missions\MissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMission extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

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
