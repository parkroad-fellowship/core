<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view department')),
            DeleteAction::make()->visible(fn () => userCan('delete department')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete department')),
            RestoreAction::make()->visible(fn () => userCan('restore department')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit department');
    }
}
