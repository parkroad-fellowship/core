<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Courses\CourseResource;
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
            ViewAction::make()->visible(fn () => userCan('view course')),
            DeleteAction::make()->visible(fn () => userCan('delete course')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete course')),
            RestoreAction::make()->visible(fn () => userCan('restore course')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit course');
    }
}
