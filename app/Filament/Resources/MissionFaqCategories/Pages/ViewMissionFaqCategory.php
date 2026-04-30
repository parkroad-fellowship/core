<?php

namespace App\Filament\Resources\MissionFaqCategories\Pages;

use App\Filament\Resources\MissionFaqCategories\MissionFaqCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionFaqCategory extends ViewRecord
{
    protected static string $resource = MissionFaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit mission faq category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission faq category');
    }
}
