<?php

namespace App\Services\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Manager;

class SMSManager extends Manager implements SMSGatewayInterface
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('prf.sms.default', 'advanta');
    }

    public function createAdvantaDriver(): SMSGatewayInterface
    {
        return new AdvantaSMSGateway;
    }

    public function createAfricasTalkingDriver(): SMSGatewayInterface
    {
        return new AfricasTalkingSMSGateway;
    }

    public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): array
    {
        return $this->driver()->send($phoneNumber, $message, $smsLoggable);
    }

    public function checkBlacklist(string $messageId): bool
    {
        return $this->driver()->checkBlacklist($messageId);
    }
}
