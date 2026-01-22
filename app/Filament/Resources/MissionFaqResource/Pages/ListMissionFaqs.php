<?php

namespace App\Filament\Resources\MissionFaqResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MissionFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissionFaqs extends ListRecords
{
    protected static string $resource = MissionFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create mission faq')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission faq');
    }
}
