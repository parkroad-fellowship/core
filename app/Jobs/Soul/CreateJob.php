<?php

namespace App\Jobs\Soul;

use App\Enums\PRFSoulDecisionType;
use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): Soul
    {
        $data = $this->data;

        $mission = Mission::query()
            ->where('ulid', $data['mission_ulid'])
            ->first();

        $classGroup = ClassGroup::query()
            ->where('ulid', $data['class_group_ulid'])
            ->first();

        return Soul::create(
            [
                'mission_id' => $mission->id,
                'class_group_id' => $classGroup->id,
                'full_name' => $data['full_name'],
                'admission_number' => Arr::get($data, 'admission_number'),
                'decision_type' => Arr::get($data, 'decision_type', PRFSoulDecisionType::SALVATION),
                'notes' => Arr::get($data, 'notes'),
            ],
        );
    }
}
