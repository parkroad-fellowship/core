<?php

namespace App\Jobs\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
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
        public ?Model $smsLoggable = null,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SMSGatewayInterface $sms): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $sms->send($this->phoneNumber, $this->message, $this->smsLoggable);
    }
}
