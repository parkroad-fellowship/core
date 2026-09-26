<?php

namespace App\Contracts\Services;

use App\Enums\PRFSMSStatus;
use App\Services\SMS\SMSResult;
use Illuminate\Database\Eloquent\Model;

interface SMSGatewayInterface
{
    /**
     * Send one SMS and record it in sms_logs (optionally against the model it concerns).
     */
    public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): SMSResult;

    public function deliveryStatus(string $messageId): PRFSMSStatus;
}
