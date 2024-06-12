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
            Actions\EditAction::make()->visible(fn () => auth()->can('editmissiontype')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('deletemissiontype')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDeletemissiontype')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restoremissiontype')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
