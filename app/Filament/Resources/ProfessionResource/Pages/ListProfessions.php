<?php

namespace App\Filament\Resources\ProfessionResource\Pages;

use App\Filament\Resources\ProfessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProfessions extends ListRecords
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit profession')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete profession')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete profession')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore profession')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
