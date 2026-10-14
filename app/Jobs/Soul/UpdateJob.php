<?php

namespace App\Jobs\Soul;

use App\Enums\PRFSoulDecisionType;
use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Soul
    {
        $soul = Soul::query()->where('ulid', $this->ulid)->firstOrFail();

        $resolved = $this->resolveULIDs($this->data, [
            'mission_ulid' => Mission::class,
            'class_group_ulid' => ClassGroup::class,
        ]);

        $attributes = [
            'mission_id' => $resolved['mission_id'],
            'class_group_id' => $resolved['class_group_id'],
            'full_name' => $resolved['full_name'],
            'admission_number' => $resolved['admission_number'] ?? null,
            'decision_type' => $resolved['decision_type'] ?? PRFSoulDecisionType::SALVATION,
            'notes' => $resolved['notes'] ?? null,
        ];

        $soul->update($attributes);

        return $soul;
    }
}
