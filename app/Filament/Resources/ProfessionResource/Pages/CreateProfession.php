<?php

namespace App\Filament\Resources\ProfessionResource\Pages;

use App\Filament\Resources\ProfessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProfession extends CreateRecord
{
    protected static string $resource = ProfessionResource::class;
    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
