<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create department')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny department');
    }
}
