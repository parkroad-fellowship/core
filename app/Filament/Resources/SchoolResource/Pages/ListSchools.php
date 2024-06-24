<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny school');
    }
}
