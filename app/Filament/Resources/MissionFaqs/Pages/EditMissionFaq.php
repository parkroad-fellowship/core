<?php

namespace App\Filament\Resources\MissionFaqs\Pages;

use App\Filament\Resources\MissionFaqs\MissionFaqResource;
use App\Models\MissionFaq;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMissionFaq extends EditRecord
{
    protected static string $resource = MissionFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(MissionFaq::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(MissionFaq::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(MissionFaq::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(MissionFaq::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionFaq::permission('edit'));
    }
}
