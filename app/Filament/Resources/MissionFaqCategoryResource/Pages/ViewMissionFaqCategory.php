<?php

namespace App\Filament\Resources\MissionFaqCategoryResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\MissionFaqCategoryResource;
use Filament\Actions;
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
