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
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create soul')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny soul');
    }
}
