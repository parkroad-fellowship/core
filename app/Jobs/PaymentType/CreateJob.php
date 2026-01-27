<?php

namespace App\Jobs\PaymentType;

use App\Models\PaymentType;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): PaymentType
    {
        return PaymentType::create($this->data);
    }
}
