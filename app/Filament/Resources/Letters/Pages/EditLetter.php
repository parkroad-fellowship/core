<?php

namespace App\Filament\Resources\Letters\Pages;

use App\Filament\Resources\Letters\LetterResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLetter extends EditRecord
{
    protected static string $resource = LetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view letter')),
            DeleteAction::make()->visible(fn () => userCan('delete letter')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete letter')),
            RestoreAction::make()->visible(fn () => userCan('restore letter')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit letter');
    }
}
