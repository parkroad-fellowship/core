<?php

namespace App\Filament\Resources\SoulResource\Pages;

use App\Filament\Resources\SoulResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoul extends EditRecord
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('create soul')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete soul')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete soul')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore soul')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny soul');
    }
}
