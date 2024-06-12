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
            Actions\EditAction::make()->visible(fn () => auth()->can('{edit}')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('{delete}')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('{forceDelete}')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('{restore}')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
