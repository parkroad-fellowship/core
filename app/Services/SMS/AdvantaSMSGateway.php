<?php

namespace App\Services\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class AdvantaSMSGateway implements SMSGatewayInterface
{
    private function getEndpointUrl(string $path): string
    {
        $baseUrl = (string) config('prf.sms.advanta.base_url', '');
        $cleanHost = preg_replace('#^https?://#', '', rtrim($baseUrl, '/'));

        return "https://{$cleanHost}/".ltrim($path, '/');
    }

    public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $formattedPhone = $phoneUtil->format(
            number: $phoneUtil->parse($phoneNumber, 'KE'),
            numberFormat: PhoneNumberFormat::E164,
        );

        $smsLog = SmsLog::create([
            'phone' => $formattedPhone,
            'message' => $message,
            'sms_loggable_id' => $smsLoggable?->getKey(),
            'sms_loggable_type' => $smsLoggable?->getMorphClass(),
        ]);

        $response = Http::post($this->getEndpointUrl('api/services/sendsms'), [
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
        $response = Http::post($this->getEndpointUrl('api/services/getdlr'), [
            'apikey' => config('prf.sms.advanta.api_key'),
            'partnerID' => config('prf.sms.advanta.partner_id'),
            'messageID' => $messageId,
        ]);

        return $response->json('delivery-description') === 'SenderName Blacklisted';
    }
}
