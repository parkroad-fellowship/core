<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use App\Filament\Resources\ClassGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClassGroup extends ViewRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create class group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny class group');
    }
}
