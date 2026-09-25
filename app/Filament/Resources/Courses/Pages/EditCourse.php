<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Course::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Course::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Course::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Course::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Course::permission('edit'));
    }
}
