<?php

namespace App\Filament\Resources\CourseResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create course')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny course');
    }
}
