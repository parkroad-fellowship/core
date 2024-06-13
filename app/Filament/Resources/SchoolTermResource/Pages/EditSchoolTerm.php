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
            Actions\EditAction::make()->visible(fn () => auth()->can('edit schoolterm')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete schoolterm')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete schoolterm')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore schoolterm')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
