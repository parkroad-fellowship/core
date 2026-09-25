<?php

namespace App\Jobs\ContactType;

use App\Models\ContactType;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): ContactType
    {
        $data = $this->data;

        $contactType = ContactType::create($data);

        return $contactType;
    }
}
