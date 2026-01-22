<?php

namespace App\Filament\Resources\Gifts\Pages;

use App\Filament\Resources\Gifts\GiftResource;
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
            ViewAction::make()->visible(fn () => userCan('view gift')),
            DeleteAction::make()->visible(fn () => userCan('delete gift')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete gift ')),
            RestoreAction::make()->visible(fn () => userCan('restore gift ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit gift');
    }
}
