<?php

namespace App\Filament\Resources\SchoolTermResource\Pages;

use App\Filament\Resources\SchoolTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolTerms extends ListRecords
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => userCan('create school term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny school term');
    }
}
