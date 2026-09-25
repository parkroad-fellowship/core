<?php

namespace App\Filament\Resources\ContactTypes\Pages;

use App\Filament\Resources\ContactTypes\ContactTypeResource;
use App\Models\ContactType;
use Filament\Resources\Pages\CreateRecord;

class CreateContactType extends CreateRecord
{
    protected static string $resource = ContactTypeResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ContactType::permission('create'));
    }
}
