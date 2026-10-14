<?php

namespace App\Filament\Resources\ContactTypes\Pages;

use App\Filament\Resources\ContactTypes\ContactTypeResource;
use App\Models\ContactType;
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
            ViewAction::make()->visible(fn() => userCan(ContactType::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(ContactType::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(ContactType::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(ContactType::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ContactType::permission('edit'));
    }
}
