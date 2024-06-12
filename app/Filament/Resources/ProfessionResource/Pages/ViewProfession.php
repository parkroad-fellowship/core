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
            Actions\EditAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('{permission}')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('{permission}')),
        ];
    }
    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
