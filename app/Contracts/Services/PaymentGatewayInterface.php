<?php

namespace App\Contracts\Services;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment transaction.
     *
     * @param  array{email: string, amount: int|string, id: string}  $data
     * @return array{status: bool, data?: array, message?: string}
     */
    public function initializeTransaction(array $data): array;

    /**
     * Verify a transaction by reference.
     *
     * @return array{status: bool, data?: array}
     */
    public function verifyTransaction(string $reference): array;

    /**
     * Verify the webhook signature from the payment provider.
     */
    public function verifyWebhook(Request $request): bool;
}
