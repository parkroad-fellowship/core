<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use App\Jobs\School\CreateJob;
use App\Jobs\School\UpdateJob;
use App\Models\School;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $missionTypeDefaultsRows = [];

    protected int|string|null $missionDefaultTypeId = null;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(School::permission('create'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Per-type defaults are saved once the school exists.
        $this->missionTypeDefaultsRows = $data['mission_type_defaults'] ?? [];
        $this->missionDefaultTypeId = $data['mission_defaults']['default_mission_type_id'] ?? null;
        unset($data['mission_type_defaults'], $data['mission_defaults']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $school = CreateJob::dispatchSync($data);
        assert($school instanceof School);

        return $school;
    }

    protected function afterCreate(): void
    {
        if (blank($this->missionTypeDefaultsRows) && blank($this->missionDefaultTypeId)) {
            return;
        }

        $school = $this->getRecord();
        assert($school instanceof School);

        UpdateJob::dispatchSync([
            'mission_defaults' => SchoolResource::rowsToMissionDefaults(
                rows: $this->missionTypeDefaultsRows,
                defaultMissionTypeId: $this->missionDefaultTypeId,
            ),
        ], $school->ulid);
    }

    protected function getRedirectUrl(): string
    {
        return SchoolResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
