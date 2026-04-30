<?php

namespace App\Jobs\ContactType;

use App\Models\ContactType;
use Illuminate\Foundation\Bus\Dispatchable;

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
    public function handle(): ContactType
    {
        $data = $this->data;

        $contactType = ContactType::create($data);

        return $contactType;
    }
}
