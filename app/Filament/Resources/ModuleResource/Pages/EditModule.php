<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view module')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete module')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete module')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore module')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit module');
    }
}
