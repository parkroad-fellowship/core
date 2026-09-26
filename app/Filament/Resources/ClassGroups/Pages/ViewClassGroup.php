<?php

namespace App\Filament\Resources\ClassGroups\Pages;

use App\Filament\Resources\ClassGroups\ClassGroupResource;
use App\Models\ClassGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClassGroup extends ViewRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(ClassGroup::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ClassGroup::permission('view'));
    }
}
