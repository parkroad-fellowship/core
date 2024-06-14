<?php

namespace App\Filament\Resources\ContactTypeResource\Pages;

use App\Filament\Resources\ContactTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactType extends EditRecord
{
    protected static string $resource = ContactTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view contact type')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete contact type')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete contact type')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore contact type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit contact type');
    }
}
