<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Filament\Resources\Memberships\MembershipResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMembership extends EditRecord
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view membership')),
            DeleteAction::make()->visible(fn () => userCan('delete membership')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete membership')),
            RestoreAction::make()->visible(fn () => userCan('restore membership')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit membership');
    }
}
