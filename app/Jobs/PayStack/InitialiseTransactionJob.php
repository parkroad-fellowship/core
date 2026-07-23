<?php

namespace App\Jobs\PayStack;

use App\Contracts\Services\PaymentGatewayInterface;
use Exception;
use Illuminate\Foundation\Bus\Dispatchable;

class InitialiseTransactionJob
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
    public function handle(PaymentGatewayInterface $payment): array
    {
        $result = $payment->initializeTransaction($this->data);

        if ($result['status']) {
            return $result['data'];
        }

        throw new Exception($result['message'] ?? 'Payment initialization failed');
    }
}
