<?php

namespace App\Jobs\PesaPal;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class CheckTransactionStatusJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $accessToken,
        public string $orderTrackingId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->accessToken}",
        ])->get(config('prf.payments.pesapal.api_url').'/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $this->orderTrackingId,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception($response->body());
    }
}
