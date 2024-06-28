<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view module')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete module')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete module')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore module')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit module');
    }
}
