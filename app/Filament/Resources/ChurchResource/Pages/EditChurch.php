<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use App\Filament\Resources\ChurchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChurch extends EditRecord
{
    protected static string $resource = ChurchResource::class;

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
