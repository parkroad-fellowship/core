<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $missionTypeDefaultsRows = [];

    protected int|string|null $missionDefaultTypeId = null;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create school');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Per-type defaults are persisted after the school record exists
        $this->missionTypeDefaultsRows = $data['mission_type_defaults'] ?? [];
        $this->missionDefaultTypeId = $data['mission_defaults']['default_mission_type_id'] ?? null;
        unset($data['mission_type_defaults'], $data['mission_defaults']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->missionTypeDefaultsRows) || filled($this->missionDefaultTypeId)) {
            $this->record->mission_defaults = SchoolResource::rowsToMissionDefaults(
                rows: $this->missionTypeDefaultsRows,
                defaultMissionTypeId: $this->missionDefaultTypeId,
            );
            $this->record->save();
        }
    }
}
