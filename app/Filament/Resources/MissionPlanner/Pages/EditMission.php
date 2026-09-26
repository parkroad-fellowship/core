<?php

namespace App\Filament\Resources\MissionPlanner\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\MissionPlanner\MissionActions;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Jobs\Mission\UpdateJob;
use App\Models\Mission;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMission extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = MissionPlannerResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Mission);

        $mission = UpdateJob::dispatchSync($data, $record->ulid);
        assert($mission instanceof Mission);

        return $mission;
    }

    protected function getHeaderActions(): array
    {
        return [
            ...MissionActions::header(),
            ViewAction::make()->visible(fn() => userCan(Mission::permission('view'))),
            MissionActions::classicView(),
            ActionGroup::make([
                DeleteAction::make()->visible(fn() => userCan(Mission::permission('delete'))),
                ForceDeleteAction::make()->visible(fn() => userCan(Mission::permission('forceDelete'))),
                RestoreAction::make()->visible(fn() => userCan(Mission::permission('restore'))),
            ])
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->tooltip('Delete'),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('edit'));
    }
}
