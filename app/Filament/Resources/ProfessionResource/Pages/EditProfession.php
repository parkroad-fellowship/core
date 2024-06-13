<?php

namespace App\Filament\Resources\ProfessionResource\Pages;

use App\Filament\Resources\ProfessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfession extends EditRecord
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create profession')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete profession')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete profession')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore  profession')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny profession');
    }
}
