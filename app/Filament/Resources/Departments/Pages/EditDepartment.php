<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
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
            ViewAction::make()->visible(fn() => userCan(Department::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Department::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Department::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Department::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Department::permission('edit'));
    }
}
