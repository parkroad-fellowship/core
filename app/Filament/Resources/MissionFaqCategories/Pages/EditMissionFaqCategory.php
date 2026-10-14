<?php

namespace App\Filament\Resources\MissionFaqCategories\Pages;

use App\Filament\Resources\MissionFaqCategories\MissionFaqCategoryResource;
use App\Models\MissionFaqCategory;
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
            ViewAction::make()->visible(fn() => userCan(MissionFaqCategory::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(MissionFaqCategory::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(MissionFaqCategory::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(MissionFaqCategory::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionFaqCategory::permission('edit'));
    }
}
