<?php

namespace App\Filament\Resources\PRFEventResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PRFEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPRFEvents extends ListRecords
{
    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(userCan('create event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny event');
    }
}
