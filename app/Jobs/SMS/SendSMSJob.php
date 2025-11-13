<?php

namespace App\Jobs\SMS;

use App\Models\SmsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class SendSMSJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phoneNumber,
        public string $message,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        $formattedPhone = $phoneUtil->format(
            number: $phoneUtil->parse($this->phoneNumber, 'KE'),
            numberFormat: PhoneNumberFormat::E164,
        );

        $smsLog = SmsLog::create([
            'phone' => $formattedPhone,
            'message' => $this->message,
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
            'message' => $this->message,
        ]);

        $responseData = $response->json();

        $smsLog->update([
            'message_id' => $response->json('responses.0.messageid'),
            'response' => $responseData,
        ]);

        CheckIfSenderIsBlacklistedJob::dispatch($smsLog)->delay(now()->addSeconds(30));
    }
}
