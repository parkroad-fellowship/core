<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view announcement')),
            DeleteAction::make()->visible(fn () => userCan('delete announcement')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete announcement')),
            RestoreAction::make()->visible(fn () => userCan('restore announcement')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit announcement');
    }
}
