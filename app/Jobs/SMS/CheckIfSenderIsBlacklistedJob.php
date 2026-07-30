<?php

namespace App\Jobs\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use App\Models\SmsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(SMSGatewayInterface $sms): void
    {
        $isBlacklisted = $sms->checkBlacklist($this->smsLog->message_id);

        $this->smsLog->update([
            'is_blacklisted' => $isBlacklisted,
        ]);
    }
}
