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
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('create church')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete church')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete church')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore church')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny church');
    }
}
