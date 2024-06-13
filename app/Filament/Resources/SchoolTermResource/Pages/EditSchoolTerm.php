<?php

namespace App\Filament\Resources\SchoolTermResource\Pages;

use App\Filament\Resources\SchoolTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolTerm extends EditRecord
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('create school term')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete school term')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete school term')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore school term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny school term');
    }
}
