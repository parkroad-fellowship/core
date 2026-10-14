<?php

namespace App\Filament\Resources\ClassGroups\Pages;

use App\Filament\Resources\ClassGroups\ClassGroupResource;
use App\Models\ClassGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClassGroup extends EditRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(ClassGroup::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(ClassGroup::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(ClassGroup::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(ClassGroup::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ClassGroup::permission('edit'));
    }
}
