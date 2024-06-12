<?php

namespace App\Filament\Resources\MissionResource\Pages;

use App\Filament\Resources\MissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissions extends ListRecords
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('{permission}')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
