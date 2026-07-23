<?php

namespace App\Services\Sms;

use App\Contracts\Services\SmsGatewayInterface;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class AdvantaSmsGateway implements SmsGatewayInterface
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

        $baseUrl = config('prf.sms.advanta.base_url');
        $response = Http::post("https://{$baseUrl}/api/services/sendsms", [
            'apikey' => config('prf.sms.advanta.api_key'),
            'partnerID' => config('prf.sms.advanta.partner_id'),
            'shortcode' => config('prf.sms.advanta.short_code'),
            'mobile' => match (app()->environment()) {
                'production' => $formattedPhone,
                default => config('prf.sms.test_phone_number'),
            },
            'message' => $message,
        ]);

        $smsLog->update([
            'message_id' => $response->json('responses.0.messageid'),
            'response' => $response->json(),
        ]);

        return [
            'message_id' => $smsLog->message_id,
            'response' => $response->json(),
        ];
    }

    public function checkBlacklist(string $messageId): bool
    {
        $baseUrl = config('prf.sms.advanta.base_url');

        $response = Http::post("https://{$baseUrl}/api/services/getdlr", [
            'apikey' => config('prf.sms.advanta.api_key'),
            'partnerID' => config('prf.sms.advanta.partner_id'),
            'messageID' => $messageId,
        ]);

        return $response->json('delivery-description') === 'SenderName Blacklisted';
    }
}
