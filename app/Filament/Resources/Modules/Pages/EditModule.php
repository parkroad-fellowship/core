<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Modules\ModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view module')),
            DeleteAction::make()->visible(fn () => userCan('delete module')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete module')),
            RestoreAction::make()->visible(fn () => userCan('restore module')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit module');
    }
}
