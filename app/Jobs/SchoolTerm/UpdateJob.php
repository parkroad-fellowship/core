<?php

namespace App\Jobs\SchoolTerm;

use App\Models\SchoolTerm;
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

    public function handle(): SchoolTerm
    {
        $schoolTerm = SchoolTerm::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $schoolTerm->update($attributes);

        return $schoolTerm;
    }
}
