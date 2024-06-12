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
            Actions\EditAction::make()->visible(fn () => auth()->can('{edit}')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('{delete}')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('{forceDelete}')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('{restore}')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
