<?php

namespace App\Filament\Resources\ContactTypes\Pages;

use App\Filament\Resources\ContactTypes\ContactTypeResource;
use App\Models\ContactType;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactType extends ViewRecord
{
    protected static string $resource = ContactTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(ContactType::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ContactType::permission('view'));
    }
}
