<?php

namespace App\Filament\Resources\PRFEvents\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\PRFEvents\PRFEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPRFEvent extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(userCan('view event')),
            DeleteAction::make()->visible(userCan('delete event')),
            ForceDeleteAction::make()->visible(userCan('forceDelete event')),
            RestoreAction::make()->visible(userCan('restore event')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit event');
    }
}
