<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create module')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny module');
    }
}
