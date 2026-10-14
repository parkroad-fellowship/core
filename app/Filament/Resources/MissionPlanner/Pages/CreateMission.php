<?php

namespace App\Filament\Resources\MissionPlanner\Pages;

use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Jobs\Mission\CreateJob;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

class CreateMission extends CreateRecord
{
    protected static string $resource = MissionPlannerResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * The school to plan for, when arriving from a school's page ("Plan a mission here").
     */
    #[Url(as: 'school')]
    public ?string $schoolULID = null;

    public function getTitle(): string
    {
        return 'Plan a mission';
    }

    public function getSubheading(): ?string
    {
        return 'It starts as “Needs approval”. Approving it tells the school and opens it for volunteers.';
    }

    protected function afterFill(): void
    {
        if ($this->schoolULID === null) {
            return;
        }

        $school = School::query()->where('ulid', $this->schoolULID)->first();

        if ($school === null) {
            return;
        }

        $this->data = [...($this->data ?? []), 'school_id' => $school->id];

        MissionPlannerResource::applySchoolDefaults(
            fn(string $key, mixed $value) => $this->data[$key] = $value,
            fn(string $key): mixed => $this->data[$key] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $mission = CreateJob::dispatchSync([
            ...array_diff_key($data, array_flip(['school_id', 'mission_type_id', 'school_term_id'])),
            'school_ulid' => School::query()->whereKey($data['school_id'])->value('ulid'),
            'mission_type_ulid' => MissionType::query()->whereKey($data['mission_type_id'])->value('ulid'),
            'school_term_ulid' => SchoolTerm::query()->whereKey($data['school_term_id'])->value('ulid'),
        ]);
        assert($mission instanceof Mission);

        return $mission;
    }

    protected function getRedirectUrl(): string
    {
        return MissionPlannerResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Mission planned')
            ->body('Next: approve it when it’s confirmed with the school.');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('create'));
    }
}
