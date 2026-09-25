<?php

namespace App\Jobs\SchoolContact;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
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

    public function handle(): SchoolContact
    {
        $schoolContact = SchoolContact::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'school_ulid' => School::class,
            'contact_type_ulid' => ContactType::class,
        ]);

        $schoolContact->update($attributes);

        return $schoolContact;
    }
}
