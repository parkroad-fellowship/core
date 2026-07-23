<?php

namespace App\Jobs\SMS;

use App\Contracts\Services\SmsGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(SmsGatewayInterface $sms): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $sms->send($this->phoneNumber, $this->message);
    }
}
