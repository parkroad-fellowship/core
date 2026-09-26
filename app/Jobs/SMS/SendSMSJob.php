<?php

namespace App\Jobs\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use App\Exceptions\IntegrationNotConfiguredException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;

#[Queue('high')]
#[Tries(3)]
#[Backoff([30, 120])]
class SendSMSJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phoneNumber,
        public string $message,
        public ?Model $smsLoggable = null,
    ) {}

    public function handle(SMSGatewayInterface $sms): void
    {
        if (!app()->environment('production')) {
            return;
        }

        try {
            $sms->send($this->phoneNumber, $this->message, $this->smsLoggable);
        } catch (IntegrationNotConfiguredException $exception) {
            Log::warning('SMS skipped: integration not configured', $exception->context());
        }
    }
}
