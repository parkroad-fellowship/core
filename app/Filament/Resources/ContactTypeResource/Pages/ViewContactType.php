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
            Actions\EditAction::make()->visible(fn () => auth()->can('edit contact_type')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete contact_type')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete contact_type')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore contact_type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view contact_type');
    }
}
