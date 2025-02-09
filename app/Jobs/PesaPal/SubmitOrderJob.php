<?php

namespace App\Jobs\PesaPal;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class SubmitOrderJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $accessToken,
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

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->accessToken}",
        ])->post(config('prf.payments.pesapal.api_url').'/Transactions/SubmitOrderRequest', [
            'currency' => 'KES',
            'callback_url' => config('prf.payments.pesapal.callback_url'),
            'notification_id' => config('prf.payments.pesapal.notification_id'),
            'id' => $data['id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'billing_address' => [
                'country_code' => 'KE',
                'line_1' => 'Parkroad Fellowship',
                'line_2' => 'Tumaini House, Aga Khan Walk',
                'city' => 'Nairobi',
                'state' => 'Nairobi',
                'postal_code' => '00200',
                'zip_code' => '00200',
                'phone_number' => $data['phone_number'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ],
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception($response->body());
    }
}
