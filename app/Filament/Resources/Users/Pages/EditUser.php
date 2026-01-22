<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
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
