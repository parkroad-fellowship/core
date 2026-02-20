<?php

namespace App\Filament\Resources\APIClients\Pages;

use App\Filament\Resources\APIClients\APIClientResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAPIClient extends EditRecord
{
    protected static string $resource = APIClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
