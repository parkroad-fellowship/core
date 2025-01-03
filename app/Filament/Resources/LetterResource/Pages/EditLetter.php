<?php

namespace App\Filament\Resources\LetterResource\Pages;

use App\Filament\Resources\LetterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLetter extends EditRecord
{
    protected static string $resource = LetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view letter')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete letter')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete letter')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore letter')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit letter');
    }
}
