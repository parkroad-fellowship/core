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

            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create mission_type')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny mission_type');
    }
}
