<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Schools\SchoolResource;
use App\Jobs\School\UpdateJob;
use App\Models\School;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSchool extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(School::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(School::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(School::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(School::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(School::permission('edit'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $school = $this->getRecord();
        assert($school instanceof School);

        $data['mission_type_defaults'] = SchoolResource::missionDefaultsToRows($school);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['mission_defaults'] = SchoolResource::rowsToMissionDefaults(
            rows: $data['mission_type_defaults'] ?? [],
            defaultMissionTypeId: $data['mission_defaults']['default_mission_type_id'] ?? null,
        );
        unset($data['mission_type_defaults']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof School);

        $school = UpdateJob::dispatchSync($data, $record->ulid);
        assert($school instanceof School);

        return $school;
    }

    protected function getRedirectUrl(): string
    {
        return SchoolResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
