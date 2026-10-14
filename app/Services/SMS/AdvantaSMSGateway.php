<?php

namespace App\Services\SMS;

use App\Enums\PRFSMSStatus;

class AdvantaSMSGateway extends SMSGateway
{
    public function name(): string
    {
        return 'advanta';
    }

    protected function deliver(string $recipient, string $message): SMSResult
    {
        $response = $this->http()->post($this->endpoint('api/services/sendsms'), [
            'apikey' => config('prf.sms.advanta.api_key'),
            'partnerID' => config('prf.sms.advanta.partner_id'),
            'shortcode' => config('prf.sms.advanta.short_code'),
            'mobile' => $recipient,
            'message' => $message,
        ]);

        $messageId = $response->json('responses.0.messageid');

        return new SMSResult(
            messageId: is_scalar($messageId) ? (string) $messageId : null,
            status: $response->successful() && (int) $response->json('responses.0.response-code') === 200
                ? PRFSMSStatus::SENT
                : PRFSMSStatus::FAILED,
            raw: (array) $response->json(),
        );
    }

    public function deliveryStatus(string $messageId): PRFSMSStatus
    {
        $description = $this
            ->http()
            ->post($this->endpoint('api/services/getdlr'), [
                'apikey' => config('prf.sms.advanta.api_key'),
                'partnerID' => config('prf.sms.advanta.partner_id'),
                'messageID' => $messageId,
            ])
            ->json('delivery-description');

        return match ($description) {
            'DeliveredToTerminal' => PRFSMSStatus::DELIVERED,
            'SenderName Blacklisted', 'DeliveryImpossible' => PRFSMSStatus::FAILED,
            null => PRFSMSStatus::UNKNOWN,
            default => PRFSMSStatus::SENT,
        };
    }

    private function endpoint(string $path): string
    {
        $host = preg_replace('#^https?://#', '', rtrim((string) config('prf.sms.advanta.base_url'), '/'));

        return "https://{$host}/" . ltrim($path, '/');
    }
}
