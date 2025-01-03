<?php

namespace App\Filament\Resources\GiftResource\Pages;

use App\Filament\Resources\GiftResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;


class EditGift extends EditRecord
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view gift')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete gift')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete gift ')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore gift ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit gift');
    }
}
