<?php

namespace App\Filament\Resources\MembershipResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\MembershipResource;
use Filament\Actions;
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
