<?php

namespace App\Jobs\SchoolContact;

use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        $data = $this->data;

        if (isset($data['school_ulid'])) {
            $school = School::query()
                ->where('ulid', $data['school_ulid'])
                ->firstOrFail();
            $data['school_id'] = $school->id;
            Arr::forget($data, 'school_ulid');
        }

        if (isset($data['contact_type_ulid'])) {
            $contactType = ContactType::query()
                ->where('ulid', $data['contact_type_ulid'])
                ->firstOrFail();
            $data['contact_type_id'] = $contactType->id;
            Arr::forget($data, 'contact_type_ulid');
        }

        return SchoolContact::query()
            ->where('ulid', $this->ulid)
            ->update($data);
    }
}
