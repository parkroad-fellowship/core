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
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create user')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete user')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete user')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore user')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny users');
    }
}
