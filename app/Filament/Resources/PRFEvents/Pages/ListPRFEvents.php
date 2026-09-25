<?php

namespace App\Filament\Resources\PRFEvents\Pages;

use App\Filament\Resources\PRFEvents\PRFEventResource;
use App\Models\PRFEvent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPRFEvents extends ListRecords
{
    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(userCan(PRFEvent::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PRFEvent::permission('viewAny'));
    }
}
