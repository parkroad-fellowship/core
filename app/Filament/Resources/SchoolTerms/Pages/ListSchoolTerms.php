<?php

namespace App\Filament\Resources\SchoolTerms\Pages;

use App\Filament\Resources\SchoolTerms\SchoolTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolTerms extends ListRecords
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create school term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny school term');
    }
}
