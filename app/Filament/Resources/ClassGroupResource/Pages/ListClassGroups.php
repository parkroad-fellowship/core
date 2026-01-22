<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClassGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassGroups extends ListRecords
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create class group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny class group');
    }
}
