<?php

namespace App\Jobs\PaymentType;

use App\Models\PaymentType;
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

    public function handle(): PaymentType
    {
        $paymentType = PaymentType::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $paymentType->update($attributes);

        return $paymentType;
    }
}
