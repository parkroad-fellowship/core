<?php

namespace App\Filament\Resources\MissionFaqCategoryResource\Pages;

use App\Filament\Resources\MissionFaqCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionFaqCategory extends EditRecord
{
    protected static string $resource = MissionFaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view mission faq category')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete mission faq category')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission faq category')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore mission faq category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission faq category');
    }
}
