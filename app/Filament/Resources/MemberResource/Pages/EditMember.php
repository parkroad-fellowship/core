<?php

namespace App\Filament\Resources\MemberResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view member')),
            DeleteAction::make()->visible(fn () => userCan('delete member')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete member ')),
            RestoreAction::make()->visible(fn () => userCan('restore member ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit member');
    }
}
