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
            Actions\DeleteAction::make()->visible(fn () => auth()->can('edit mission')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete mission')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete mission')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore mission')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit mission');
    }
}
