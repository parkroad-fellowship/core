<?php

namespace App\Filament\Resources\MissionQuestions\Pages;

use App\Filament\Resources\MissionQuestions\MissionQuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMissionQuestion extends EditRecord
{
    protected static string $resource = MissionQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view mission question')),
            DeleteAction::make()->visible(fn () => userCan('delete mission question')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission question')),
            RestoreAction::make()->visible(fn () => userCan('restore mission question')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission question');
    }
}
