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
            Actions\EditAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('{permission}')),
        ];
    }
    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
