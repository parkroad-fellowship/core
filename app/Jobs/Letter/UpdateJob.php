<?php

namespace App\Jobs\Letter;

use App\Models\Letter;
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
        Letter::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($this->data);
    }
}
