<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\ClassGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassGroup extends EditRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view class group')),
            DeleteAction::make()->visible(fn () => userCan('delete class group')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete class group')),
            RestoreAction::make()->visible(fn () => userCan('restore class group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit class group');
    }
}
