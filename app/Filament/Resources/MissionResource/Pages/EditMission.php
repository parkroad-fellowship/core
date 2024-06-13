<?php

namespace App\Filament\Resources\MissionResource\Pages;

use App\Filament\Resources\MissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMission extends EditRecord
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create mission')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete mission')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete mission')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore  mission')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny mission');
    }
}
