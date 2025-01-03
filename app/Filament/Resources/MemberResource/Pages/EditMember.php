<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view member')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete member')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete member ')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore member ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit member');
    }
}
