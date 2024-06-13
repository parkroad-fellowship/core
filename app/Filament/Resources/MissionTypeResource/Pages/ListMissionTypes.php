<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use App\Filament\Resources\MissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissionTypes extends ListRecords
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit mission_type')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete mission_type')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete mission_type')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore mission_type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
