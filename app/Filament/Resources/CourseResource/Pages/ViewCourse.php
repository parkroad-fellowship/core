<?php

namespace App\Filament\Resources\CourseResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourse extends ViewRecord
{
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
