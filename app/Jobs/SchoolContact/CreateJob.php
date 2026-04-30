<?php

namespace App\Jobs\SchoolContact;

use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): SchoolContact
    {
        $data = $this->data;

        $school = School::query()
            ->where('ulid', $data['school_ulid'])
            ->firstOrFail();
        $data['school_id'] = $school->id;
        Arr::forget($data, 'school_ulid');

        $contactType = ContactType::query()
            ->where('ulid', $data['contact_type_ulid'])
            ->firstOrFail();
        $data['contact_type_id'] = $contactType->id;
        Arr::forget($data, 'contact_type_ulid');

        // If the preffered_name is empty/null, set it to the name field trimmed

        if (Arr::has($data, 'preferred_name')) {
            $data['preferred_name'] = trim($data['preferred_name']);
        } else {
            $data['preferred_name'] = trim($data['name']);
        }

        return SchoolContact::create($data);

    }
}
