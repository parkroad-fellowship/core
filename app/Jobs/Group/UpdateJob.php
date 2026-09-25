<?php

namespace App\Jobs\Group;

use App\Models\Group;
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

    public function handle(): Group
    {
        $group = Group::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $group->update($attributes);

        return $group;
    }
}
