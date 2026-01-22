<?php

namespace App\Filament\Resources\MissionFaqCategories\Pages;

use App\Filament\Resources\MissionFaqCategories\MissionFaqCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionFaqCategory extends CreateRecord
{
    protected static string $resource = MissionFaqCategoryResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission faq category');
    }
}
