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
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create department')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete department')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete department')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore department')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny department');
    }
}
