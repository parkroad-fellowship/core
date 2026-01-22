<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny school');
    }
}
