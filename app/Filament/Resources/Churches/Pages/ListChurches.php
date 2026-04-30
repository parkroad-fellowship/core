<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Filament\Resources\Churches\ChurchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChurches extends ListRecords
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create church')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny church');
    }
}
