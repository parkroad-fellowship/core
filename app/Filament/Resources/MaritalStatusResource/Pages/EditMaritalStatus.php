<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaritalStatus extends EditRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view marital status')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete marital status')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete marital status')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore marital status')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view marital status');
    }
}
