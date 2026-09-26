<?php

namespace App\Jobs\SchoolContact;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): SchoolContact
    {
        $attributes = $this->resolveULIDs($this->data, [
            'school_ulid' => School::class,
            'contact_type_ulid' => ContactType::class,
        ]);

        $name = is_string($attributes['name'] ?? null) ? trim($attributes['name']) : '';
        $preferredName = is_string($attributes['preferred_name'] ?? null) ? trim($attributes['preferred_name']) : '';

        $attributes['name'] = $name;
        $attributes['preferred_name'] = $preferredName !== '' ? $preferredName : $name;

        return SchoolContact::create($attributes);
    }
}
