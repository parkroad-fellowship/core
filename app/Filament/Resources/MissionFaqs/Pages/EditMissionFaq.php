<?php

namespace App\Filament\Resources\MissionFaqs\Pages;

use App\Filament\Resources\MissionFaqs\MissionFaqResource;
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
            ViewAction::make()->visible(fn () => userCan('view mission faq')),
            DeleteAction::make()->visible(fn () => userCan('delete mission faq')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission faq')),
            RestoreAction::make()->visible(fn () => userCan('restore mission faq')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission faq');
    }
}
