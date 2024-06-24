<?php

namespace App\Filament\Resources\SoulResource\Pages;

use App\Filament\Resources\SoulResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSoul extends ViewRecord
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->user()->can('edit soul')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view soul');
    }
}
