<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;


class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view department')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete department')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete department')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore department')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit department');
    }
}
