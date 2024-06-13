<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaritalStatus extends EditRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create marital_status')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete marital_status')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny marital_status');
    }
}
