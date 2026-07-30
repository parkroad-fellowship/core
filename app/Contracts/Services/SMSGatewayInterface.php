<?php

namespace App\Contracts\Services;

use Illuminate\Database\Eloquent\Model;

interface SMSGatewayInterface
{
    /**
     * Send an SMS message to a phone number.
     *
     * @return array{message_id: string|null, response: array}
     */
    public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): array;

    /**
     * Check if a sender is blacklisted by the SMS provider.
     */
    public function checkBlacklist(string $messageId): bool;
}
