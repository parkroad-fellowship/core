<?php

namespace App\Filament\Resources\Gifts\Pages;

use App\Filament\Resources\Gifts\GiftResource;
use App\Models\Gift;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGift extends EditRecord
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Gift::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Gift::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Gift::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Gift::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Gift::permission('edit'));
    }
}
