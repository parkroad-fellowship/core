<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit user')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete user')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete user')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore user')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit users');
    }
}
