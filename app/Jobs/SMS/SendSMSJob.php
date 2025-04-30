<?php

namespace App\Jobs\SMS;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

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
        // if (! app()->environment('production')) {
        //     return;
        // }

        $baseUrl = config('prf.sms.advanta.base_url');
        Http::post("https://{$baseUrl}/api/services/sendsms", [
            'apikey' => config('prf.sms.advanta.api_key'),
            'partnerID' => config('prf.sms.advanta.partner_id'),
            'shortcode' => config('prf.sms.advanta.short_code'),
            // 'mobile' => $this->phoneNumber,
            'mobile' => '254703175638',
            'message' => $this->message,
        ]);
    }
}
