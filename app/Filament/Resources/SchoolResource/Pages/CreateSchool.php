<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('create school');
    }
}
