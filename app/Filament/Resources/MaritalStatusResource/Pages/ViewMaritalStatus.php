<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaritalStatus extends ViewRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit marital_status')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete marital_status')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete marital_status')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore marital_status')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
