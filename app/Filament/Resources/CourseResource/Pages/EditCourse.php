<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;


class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view course')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete course')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete course')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore course')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit course');
    }
}
