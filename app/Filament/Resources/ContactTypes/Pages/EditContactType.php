<?php

namespace App\Filament\Resources\ContactTypes\Pages;

use App\Filament\Resources\ContactTypes\ContactTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContactType extends EditRecord
{
    protected static string $resource = ContactTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view contact type')),
            DeleteAction::make()->visible(fn () => userCan('delete contact type')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete contact type')),
            RestoreAction::make()->visible(fn () => userCan('restore contact type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit contact type');
    }
}
