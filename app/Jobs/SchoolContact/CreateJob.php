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

        return SchoolContact::create($data);

    }
}
