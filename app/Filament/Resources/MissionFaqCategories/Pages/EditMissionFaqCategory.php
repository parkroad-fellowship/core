<?php

namespace App\Filament\Resources\MissionFaqCategories\Pages;

use App\Filament\Resources\MissionFaqCategories\MissionFaqCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMissionFaqCategory extends EditRecord
{
    protected static string $resource = MissionFaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view mission faq category')),
            DeleteAction::make()->visible(fn () => userCan('delete mission faq category')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission faq category')),
            RestoreAction::make()->visible(fn () => userCan('restore mission faq category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission faq category');
    }
}
