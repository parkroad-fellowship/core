<?php

namespace App\Jobs\Soul;

use App\Enums\PRFSoulDecisionType;
use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $soulUlid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $formData = $this->data;
        $soulUlid = $this->soulUlid;

        $mission = Mission::query()
            ->where('ulid', $formData['mission_ulid'])
            ->first();

        $classGroup = ClassGroup::query()
            ->where('ulid', $formData['class_group_ulid'])
            ->first();

        Soul::query()
            ->where('ulid', $soulUlid)
            ->update([
                'mission_id' => $mission->id,
                'class_group_id' => $classGroup->id,
                'full_name' => $formData['full_name'],
                'admission_number' => Arr::get($formData, 'admission_number'),
                'decision_type' => Arr::get($formData, 'decision_type', PRFSoulDecisionType::SALVATION),
                'notes' => Arr::get($formData, 'notes'),
            ]);
    }
}
