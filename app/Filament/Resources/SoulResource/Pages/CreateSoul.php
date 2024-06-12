<?php

namespace App\Filament\Resources\SoulResource\Pages;

use App\Filament\Resources\SoulResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSoul extends CreateRecord
{
    protected static string $resource = SoulResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
