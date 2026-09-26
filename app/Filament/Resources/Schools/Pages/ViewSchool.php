<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Forms\Schemas\SchoolSchema;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Mission;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchool extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('planMission')
                ->label('Plan a mission here')
                ->icon('heroicon-m-calendar-days')
                ->url(fn(School $record): string => SchoolSchema::planMissionUrl($record))
                ->visible(fn(School $record): bool => !$record->trashed() && userCan(Mission::permission('create'))),

            EditAction::make()
                ->color('gray')
                ->visible(fn() => userCan(School::permission('edit'))),

            Action::make('recalculateDistance')
                ->label('Work out distance again')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(fn(School $record) => SchoolResource::recalculateDistance($record))
                ->visible(
                    fn(School $record): bool => (
                        SchoolSchema::isPinned($record->location) && userCan(School::permission('edit'))
                    ),
                ),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(School::permission('view'));
    }
}
