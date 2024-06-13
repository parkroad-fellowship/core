<?php

namespace App\Filament\Resources\SoulResource\Pages;

use App\Filament\Resources\SoulResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSouls extends ListRecords
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit soul')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete soul')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete soul')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore soul')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('list soul');
    }
}
