<?php

namespace App\Filament\Resources\ProfessionResource\Pages;

use App\Filament\Resources\ProfessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProfession extends ViewRecord
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('{edit}')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('{delete}')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('{forceDelete}')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('{restore}')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
