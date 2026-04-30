<?php

namespace App\Filament\Resources\Souls\Pages;

use App\Filament\Resources\Souls\SoulResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSouls extends ListRecords
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create soul')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny soul');
    }
}
