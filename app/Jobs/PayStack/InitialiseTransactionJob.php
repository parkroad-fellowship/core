<?php

namespace App\Jobs\PayStack;

use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

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
    public function handle(): array
    {

        $data = $this->data;

        // Call Paystack API to initialize transaction
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('prf.payments.paystack.secret_key'),
        ])
            ->post(config('prf.payments.paystack.base_url').'/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'],
                'callback_url' => config('prf.payments.paystack.callback_url'),
                'reference' => $data['id'],
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new Exception($response->body());
    }
}
