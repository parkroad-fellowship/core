<?php

namespace App\Filament\Resources\MissionFaqResource\Pages;

use App\Filament\Resources\MissionFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;



class EditMissionFaq extends EditRecord
{
    protected static string $resource = MissionFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view mission faq')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete mission faq')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission faq')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore mission faq')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission faq');
    }
}
