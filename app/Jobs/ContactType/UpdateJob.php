<?php

namespace App\Jobs\ContactType;

use App\Models\ContactType;
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

    public function handle(): ContactType
    {
        $contactType = ContactType::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $contactType->update($attributes);

        return $contactType;
    }
}
