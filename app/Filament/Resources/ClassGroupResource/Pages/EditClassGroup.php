<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use App\Filament\Resources\ClassGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassGroup extends EditRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view class group')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete class group')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete class group')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore class group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit class group');
    }
}
