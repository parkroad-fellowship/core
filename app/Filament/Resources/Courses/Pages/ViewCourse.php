<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCourse extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit course')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view course');
    }
}
