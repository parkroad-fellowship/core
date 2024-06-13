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
            Actions\EditAction::make()->visible(fn () => auth()->can('edit department')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete department')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete department')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore department')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit department');
    }
}
