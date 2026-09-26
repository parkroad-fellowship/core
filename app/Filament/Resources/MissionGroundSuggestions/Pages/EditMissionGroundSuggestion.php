<?php

namespace App\Filament\Resources\MissionGroundSuggestions\Pages;

use App\Filament\Resources\MissionGroundSuggestions\MissionGroundSuggestionResource;
use App\Jobs\MissionGroundSuggestion\UpdateJob;
use App\Models\MissionGroundSuggestion;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMissionGroundSuggestion extends EditRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(MissionGroundSuggestion::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(MissionGroundSuggestion::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(MissionGroundSuggestion::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(MissionGroundSuggestion::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionGroundSuggestion::permission('edit'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof MissionGroundSuggestion);

        $suggestion = UpdateJob::dispatchSync($data, $record->ulid);
        assert($suggestion instanceof MissionGroundSuggestion);

        return $suggestion;
    }
}
