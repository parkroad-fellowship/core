<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ChurchResource;
use Filament\Actions;
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
