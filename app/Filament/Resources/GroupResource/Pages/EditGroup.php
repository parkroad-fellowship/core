<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view group')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete group')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete group')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit group');
    }
}
