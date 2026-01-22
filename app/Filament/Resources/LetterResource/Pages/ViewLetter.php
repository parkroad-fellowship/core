<?php

namespace App\Filament\Resources\LetterResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\LetterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLetter extends ViewRecord
{
    protected static string $resource = LetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit lesson')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view lesson');
    }
}
