<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view user')),
            DeleteAction::make()->visible(fn () => userCan('delete user')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete user')),
            RestoreAction::make()->visible(fn () => userCan('restore user')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit user');
    }
}
