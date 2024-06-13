<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use App\Filament\Resources\MissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionType extends EditRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create mission_type')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete mission_type')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny mission_type');
    }
}
