<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Filament\Resources\Churches\ChurchResource;
use App\Models\Church;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChurches extends ListRecords
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(Church::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Church::permission('viewAny'));
    }
}
