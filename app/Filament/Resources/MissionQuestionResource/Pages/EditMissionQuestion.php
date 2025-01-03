<?php

namespace App\Filament\Resources\MissionQuestionResource\Pages;

use App\Filament\Resources\MissionQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionQuestion extends EditRecord
{
    protected static string $resource = MissionQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view mission question')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete mission question')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission question')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore mission question')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission question');
    }
}
