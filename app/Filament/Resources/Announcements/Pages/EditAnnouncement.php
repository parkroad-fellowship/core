<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Announcement::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Announcement::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Announcement::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Announcement::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Announcement::permission('edit'));
    }
}
