<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use App\Filament\Resources\ChurchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChurch extends ViewRecord
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit church')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete church')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete church')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore church')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view church');
    }
}
