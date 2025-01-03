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
            Actions\ViewAction::make()->visible(fn () => userCan('view contact type')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete contact type')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete contact type')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore contact type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit contact type');
    }
}
