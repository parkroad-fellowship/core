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
            Actions\EditAction::make()->visible(fn () => auth()->can('edit gift')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete gift')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete gift')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore gift')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit gift');
    }
}
