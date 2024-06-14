<?php

namespace App\Filament\Resources\SchoolTermResource\Pages;

use App\Filament\Resources\SchoolTermResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolTerm extends CreateRecord
{
    protected static string $resource = SchoolTermResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('create school term');
    }
}
