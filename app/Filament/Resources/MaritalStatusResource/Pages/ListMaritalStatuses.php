<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaritalStatuses extends ListRecords
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create marital status')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny marital status');
    }
}
