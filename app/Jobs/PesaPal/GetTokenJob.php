<?php

namespace App\Jobs\PesaPal;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class GetTokenJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post(config('prf.payments.pesapal.api_url').'/Auth/RequestToken', [
            'consumer_key' => config('prf.payments.pesapal.consumer_key'),
            'consumer_secret' => config('prf.payments.pesapal.consumer_secret'),
        ]);

        if ($response->successful()) {
            $results = $response->json();

            return match ($results['status']) {
                '200' => $results['token'],
                '500' => throw new \Exception($results['error']['code']),
                default => throw new \Exception('Failed to get token from PesaPal'),
            };
        } else {
            throw new \Exception('Invalid request');
        }
    }
}
