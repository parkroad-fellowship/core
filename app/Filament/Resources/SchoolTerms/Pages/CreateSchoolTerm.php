<?php

namespace App\Filament\Resources\SchoolTerms\Pages;

use App\Filament\Resources\SchoolTerms\SchoolTermResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolTerm extends CreateRecord
{
    protected static string $resource = SchoolTermResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create school term');
    }
}
