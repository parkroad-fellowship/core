<?php

namespace App\Jobs\PaymentType;

use App\Models\PaymentType;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): PaymentType
    {
        return PaymentType::create($this->data);
    }
}
