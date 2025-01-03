<?php

namespace App\Filament\Resources\MembershipResource\Pages;

use App\Filament\Resources\MembershipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMembership extends EditRecord
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view membership')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete membership')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete membership')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore membership')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit membership');
    }
}
