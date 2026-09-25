<?php

namespace App\Filament\Resources\Souls\Pages;

use App\Filament\Resources\Souls\SoulResource;
use App\Models\Soul;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSouls extends ListRecords
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(Soul::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Soul::permission('viewAny'));
    }
}
