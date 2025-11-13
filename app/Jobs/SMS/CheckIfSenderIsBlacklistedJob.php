<?php

namespace App\Jobs\SMS;

use App\Models\SmsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class CheckIfSenderIsBlacklistedJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SmsLog $smsLog,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $smsLog = $this->smsLog;

        $baseUrl = config('prf.sms.advanta.base_url');

        $response = Http::post("https://{$baseUrl}/api/services/getdlr", [
            'apikey' => config('prf.sms.advanta.api_key'),
            'partnerID' => config('prf.sms.advanta.partner_id'),
            'messageID' => $smsLog->message_id,
        ]);

        $smsLog->update([
            'is_blacklisted' => $response->json('delivery-description') === 'SenderName Blacklisted',
        ]);
    }
}
