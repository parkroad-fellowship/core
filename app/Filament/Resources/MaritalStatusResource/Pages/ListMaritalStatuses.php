<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaritalStatuses extends ListRecords
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit maritalstatus')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete maritalstatus')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete maritalstatus')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore maritalstatus')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
