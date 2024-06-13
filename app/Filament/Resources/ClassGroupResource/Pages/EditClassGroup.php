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
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create class_group')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete class_group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny class_group');
    }
}
