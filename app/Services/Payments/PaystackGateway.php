<?php

namespace App\Services\Payments;

use App\Contracts\Services\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGatewayInterface
{
    private function getBaseUrl(): string
    {
        $baseUrl = (string) config('prf.payments.paystack.base_url', 'https://api.paystack.co');

        return rtrim($baseUrl, '/');
    }

    public function initializeTransaction(array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('prf.payments.paystack.secret_key'),
        ])
            ->post($this->getBaseUrl().'/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'],
                'callback_url' => config('prf.payments.paystack.callback_url'),
                'reference' => $data['id'],
            ]);

        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json('data'),
            ];
        }

        return [
            'status' => false,
            'message' => $response->body(),
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('prf.payments.paystack.secret_key'),
        ])->get($this->getBaseUrl()."/transaction/verify/{$reference}");

        return [
            'status' => $response->successful(),
            'data' => $response->json('data'),
            'response' => $response->json(),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Paystack-Signature');
        $secretKey = (string) config('prf.payments.paystack.secret_key');

        if (! $signature || $secretKey === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $request->getContent(), $secretKey);

        return hash_equals($computed, $signature);
    }
}
