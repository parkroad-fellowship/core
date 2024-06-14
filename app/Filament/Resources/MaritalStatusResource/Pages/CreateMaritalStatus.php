<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaritalStatus extends CreateRecord
{
    protected static string $resource = MaritalStatusResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('create marital status');
    }
}
