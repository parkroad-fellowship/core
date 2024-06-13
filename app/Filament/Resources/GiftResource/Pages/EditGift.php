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
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create gift')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete gift')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('force delete gift ')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore gift ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny gift');
    }
}
