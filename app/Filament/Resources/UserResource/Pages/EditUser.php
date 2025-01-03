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
            Actions\ViewAction::make()->visible(fn () => userCan('view user')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete user')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete user')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore user')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit user');
    }
}
