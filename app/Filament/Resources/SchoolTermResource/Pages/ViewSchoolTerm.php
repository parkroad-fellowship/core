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
            Actions\EditAction::make()->visible(fn () => auth()->can('edit school_term')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete school_term')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete school_term')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore school_term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view school_term');
    }
}
