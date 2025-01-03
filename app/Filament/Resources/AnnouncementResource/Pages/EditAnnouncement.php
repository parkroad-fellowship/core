<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;



class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view announcement')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete announcement')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete announcement')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore announcement')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit announcement');
    }
}
