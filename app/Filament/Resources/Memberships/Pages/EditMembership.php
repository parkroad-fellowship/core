<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Filament\Resources\Memberships\MembershipResource;
use App\Models\Membership;
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
            ViewAction::make()->visible(fn() => userCan(Membership::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Membership::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Membership::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Membership::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Membership::permission('edit'));
    }
}
