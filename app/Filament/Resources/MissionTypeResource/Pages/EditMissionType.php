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
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view mission type')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete mission type')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete mission type')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore  mission')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit mission type');
    }
}
