<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view school')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete school')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete school')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit school');
    }
}
