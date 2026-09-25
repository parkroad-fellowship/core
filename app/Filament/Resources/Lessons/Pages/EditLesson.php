<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
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
            ViewAction::make()->visible(fn() => userCan(Lesson::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Lesson::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Lesson::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Lesson::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Lesson::permission('edit'));
    }
}
