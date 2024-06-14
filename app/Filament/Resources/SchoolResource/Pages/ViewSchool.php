<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSchool extends ViewRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->user()->can('edit school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view school');
    }
}
