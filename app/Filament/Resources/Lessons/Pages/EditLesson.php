<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view lesson')),
            DeleteAction::make()->visible(fn () => userCan('delete lesson')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete lesson')),
            RestoreAction::make()->visible(fn () => userCan('restore lesson')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit lesson');
    }
}
