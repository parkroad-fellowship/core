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
            Actions\EditAction::make()->visible(fn () => auth()->can('edit school')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete school')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete school')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
