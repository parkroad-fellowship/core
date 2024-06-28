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
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view course')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete course')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete course')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore course')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit course');
    }
}
