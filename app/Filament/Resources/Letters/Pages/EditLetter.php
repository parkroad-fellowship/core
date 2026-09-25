<?php

namespace App\Filament\Resources\Letters\Pages;

use App\Filament\Resources\Letters\LetterResource;
use App\Models\Letter;
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
            ViewAction::make()->visible(fn() => userCan(Letter::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Letter::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Letter::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Letter::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Letter::permission('edit'));
    }
}
