<?php

namespace App\Filament\Resources\ContactTypeResource\Pages;

use App\Filament\Resources\ContactTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContactType extends ViewRecord
{
    protected static string $resource = ContactTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->user()->can('edit contact type')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view contact type');
    }
}
