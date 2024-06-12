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
            Actions\DeleteAction::make()->visible(fn () => auth()->can('editmission')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('deletemission')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDeletemission')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restoremission')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
