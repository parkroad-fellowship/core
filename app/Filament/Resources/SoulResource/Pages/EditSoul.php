<?php

namespace App\Filament\Resources\SoulResource\Pages;

use App\Filament\Resources\SoulResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoul extends EditRecord
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
