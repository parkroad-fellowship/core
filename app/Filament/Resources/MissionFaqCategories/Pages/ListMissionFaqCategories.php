<?php

namespace App\Filament\Resources\MissionFaqCategories\Pages;

use App\Filament\Resources\MissionFaqCategories\MissionFaqCategoryResource;
use App\Models\MissionFaqCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMissionFaqCategories extends ListRecords
{
    protected static string $resource = MissionFaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(MissionFaqCategory::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionFaqCategory::permission('viewAny'));
    }
}
