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
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create class group')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete class group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny class group');
    }
}
