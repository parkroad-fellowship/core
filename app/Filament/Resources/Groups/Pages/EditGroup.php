<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view group')),
            DeleteAction::make()->visible(fn () => userCan('delete group')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete group')),
            RestoreAction::make()->visible(fn () => userCan('restore group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit group');
    }
}
