<?php

namespace App\Jobs\MaritalStatus;

use App\Models\MaritalStatus;
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

    public function handle(): MaritalStatus
    {
        $maritalStatus = MaritalStatus::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $maritalStatus->update($attributes);

        return $maritalStatus;
    }
}
