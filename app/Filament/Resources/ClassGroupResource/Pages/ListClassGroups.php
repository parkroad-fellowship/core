<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use App\Filament\Resources\ClassGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassGroups extends ListRecords
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit class_group')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete class_group')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete class_group')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore class_group')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
