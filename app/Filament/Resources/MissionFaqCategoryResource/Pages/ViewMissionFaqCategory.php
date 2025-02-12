<?php

namespace App\Filament\Resources\MissionFaqCategoryResource\Pages;

use App\Filament\Resources\MissionFaqCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionFaqCategory extends ViewRecord
{
    protected static string $resource = MissionFaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => userCan('edit mission faq category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission faq category');
    }
}
