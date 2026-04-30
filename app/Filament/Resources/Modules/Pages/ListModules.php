<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use Filament\Actions\CreateAction;
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
