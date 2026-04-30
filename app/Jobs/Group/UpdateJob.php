<?php

namespace App\Jobs\Group;

use App\Models\Group;
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
        Group::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($this->data);
    }
}
