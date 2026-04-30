<?php

namespace App\Jobs\MissionOfflineMember;

use App\Models\MissionOfflineMember;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        MissionOfflineMember::query()
            ->where('ulid', $this->ulid)
            ->update($this->data);
    }
}
