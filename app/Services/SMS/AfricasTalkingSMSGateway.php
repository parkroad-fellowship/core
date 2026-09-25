<?php

namespace App\Services\SMS;

use App\Enums\PRFSMSStatus;

class AfricasTalkingSMSGateway extends SMSGateway
{
    public function name(): string
    {
        return 'africas_talking';
    }

    protected function deliver(string $recipient, string $message): SMSResult
    {
        $response = $this
            ->http()
            ->withHeaders(['apiKey' => config('prf.sms.africas_talking.api_key')])
            ->asForm()
            ->post('https://api.africastalking.com/version1/messaging', array_filter([
                'username' => config('prf.sms.africas_talking.username'),
                'to' => $recipient,
                'message' => $message,
                'from' => config('prf.sms.africas_talking.from'),
            ]));

        $recipientResult = (array) $response->json('SMSMessageData.Recipients.0');
        $messageId = $recipientResult['messageId'] ?? null;
        $cost = $recipientResult['cost'] ?? null;

        return new SMSResult(
            messageId: is_scalar($messageId) ? (string) $messageId : null,
            status: ($recipientResult['status'] ?? null) === 'Success' ? PRFSMSStatus::SENT : PRFSMSStatus::FAILED,
            cost: is_scalar($cost) ? (string) $cost : null,
            raw: (array) $response->json(),
        );
    }
}
