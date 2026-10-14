<?php

namespace App\Jobs\Pledge;

use App\Models\Pledge;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Pledge
    {
        $pledge = Pledge::query()->where('ulid', $this->ulid)->firstOrFail();

        $pledge->update($this->data);

        return $pledge;
    }
}
