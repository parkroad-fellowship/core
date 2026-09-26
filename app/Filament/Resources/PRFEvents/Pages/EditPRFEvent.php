<?php

namespace App\Filament\Resources\PRFEvents\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\PRFEvents\PRFEventResource;
use App\Models\PRFEvent;
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
            ViewAction::make()->visible(userCan(PRFEvent::permission('view'))),
            DeleteAction::make()->visible(userCan(PRFEvent::permission('delete'))),
            ForceDeleteAction::make()->visible(userCan(PRFEvent::permission('forceDelete'))),
            RestoreAction::make()->visible(userCan(PRFEvent::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PRFEvent::permission('edit'));
    }
}
