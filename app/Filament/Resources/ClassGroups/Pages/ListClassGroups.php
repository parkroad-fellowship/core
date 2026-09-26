<?php

namespace App\Filament\Resources\ClassGroups\Pages;

use App\Filament\Resources\ClassGroups\ClassGroupResource;
use App\Models\ClassGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassGroups extends ListRecords
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(ClassGroup::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ClassGroup::permission('viewAny'));
    }
}
