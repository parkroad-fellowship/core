<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view lesson')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete lesson')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete lesson')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore lesson')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit lesson');
    }
}
