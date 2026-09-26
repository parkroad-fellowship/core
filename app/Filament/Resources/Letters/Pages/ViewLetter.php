<?php

namespace App\Filament\Resources\Letters\Pages;

use App\Filament\Resources\Letters\LetterResource;
use App\Models\Lesson;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLetter extends ViewRecord
{
    protected static string $resource = LetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(Lesson::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Lesson::permission('view'));
    }
}
