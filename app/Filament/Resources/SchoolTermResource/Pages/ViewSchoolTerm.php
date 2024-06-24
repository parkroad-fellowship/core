<?php

namespace App\Filament\Resources\SchoolTermResource\Pages;

use App\Filament\Resources\SchoolTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSchoolTerm extends ViewRecord
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->user()->can('edit school term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view school term');
    }
}
