<?php

namespace App\Filament\Resources\MissionFaqCategoryResource\Pages;

use App\Filament\Resources\MissionFaqCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissionFaqCategories extends ListRecords
{
    protected static string $resource = MissionFaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => userCan('create mission faq category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission faq category');
    }
}
