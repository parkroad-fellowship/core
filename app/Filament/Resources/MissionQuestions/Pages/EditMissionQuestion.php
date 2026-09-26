<?php

namespace App\Filament\Resources\MissionQuestions\Pages;

use App\Filament\Resources\MissionQuestions\MissionQuestionResource;
use App\Models\MissionQuestion;
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
            ViewAction::make()->visible(fn() => userCan(MissionQuestion::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(MissionQuestion::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(MissionQuestion::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(MissionQuestion::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionQuestion::permission('edit'));
    }
}
