<?php

namespace App\Services\Sms;

use App\Contracts\Services\SmsGatewayInterface;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class AfricasTalkingSmsGateway implements SmsGatewayInterface
{
    public function send(string $phoneNumber, string $message): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $formattedPhone = $phoneUtil->format(
            number: $phoneUtil->parse($phoneNumber, 'KE'),
            numberFormat: PhoneNumberFormat::E164,
        );

        $smsLog = SmsLog::create([
            'phone' => $formattedPhone,
            'message' => $message,
        ]);

        $response = Http::withHeaders([
            'apiKey' => config('prf.africas_talking.api_key'),
            'Accept' => 'application/json',
        ])->post('https://api.africastalking.com/version1/messaging', [
            'username' => config('prf.africas_talking.username'),
            'to' => $formattedPhone,
            'message' => $message,
            'from' => config('prf.app.africas_talking.from'),
        ]);

        $recipient = $response->json('SMSMessageData.Recipients.0') ?? [];

        $smsLog->update([
            'message_id' => $recipient['messageId'] ?? null,
            'response' => $response->json(),
        ]);

        return [
            'message_id' => $smsLog->message_id,
            'response' => $response->json(),
        ];
    }

    public function checkBlacklist(string $messageId): bool
    {
        return false;
    }
}
