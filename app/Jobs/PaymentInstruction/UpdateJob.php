<?php

namespace App\Jobs\PaymentInstruction;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\PaymentInstruction;
use App\Models\Requisition;
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

    public function handle(): PaymentInstruction
    {
        $paymentInstruction = PaymentInstruction::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'requisition_ulid' => Requisition::class,
        ]);

        $paymentInstruction->update($attributes);

        return $paymentInstruction;
    }
}
